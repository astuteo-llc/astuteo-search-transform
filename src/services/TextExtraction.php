<?php

namespace astuteo\astuteosearchtransform\services;

use astuteo\astuteosearchtransform\services\craft5\Matrix;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\StringHelper;
use Exception;

/**
 * Text Extraction provides methods for extracting and transforming text
 * for search operations, primarily for use with Algolia search.
 *
 * @package   AstuteoSearchTransform
 * @since     5.4.0
 */
class TextExtraction extends Component
{
    /**
     * Fields that should be excluded from extraction as they're metadata
     */
    private const META_FIELDS = [
        'id', 'uid', 'dateCreated', 'dateUpdated', 'siteId', 'enabled',
        'status', 'slug', 'uri', 'authorId', 'archived', 'sectionId', 'typeId',
        'revisionId', 'postDate', 'expiryDate'
    ];

    /**
     * Default field types to extract text from
     */
    public const DEFAULT_EXTRACT_FIELDS = ['text', 'heading'];

    /**
     * Extracts text from a matrix field.
     *
     * @param object $matrix The matrix field object
     * @param string $handle The handle of the matrix field
     * @param array<string> $include Optional array of block types to include
     * @return string The extracted and cleaned text
     */
    public function extractMatrixText(object $matrix, string $handle, array $include = []): string
    {
        return $this->matrixCopy($matrix, $handle, $include);
    }

    /**
     * Extracts text from a matrix field with optional field filtering
     *
     * @param mixed $field The matrix field
     * @param array $fieldHandles Field handles to filter by (exclude or include)
     * @param bool $includeOnly If true, only include specified handles; if false, exclude them
     * @return string The extracted text content
     */
    public function extractTextFromMatrix($field, array $fieldHandles = [], bool $includeOnly = false): string
    {
        return (new Matrix)->extractText($field, $fieldHandles, $includeOnly);
    }

    /**
     * Extracts text from an entry.
     *
     * @param object $entry The entry object
     * @param array<string> $include Optional array of fields to include
     * @return string The extracted and cleaned text
     */
    public function extractEntryText(object $entry, array $include = []): string
    {
        $fields = $entry->toArray();
        $text = '';

        foreach ($fields as $field => $value) {
            if ($this->isMetaField($field)) {
                continue;
            }

            if (empty($include) || in_array($field, $include, true)) {
                $text .= ' ' . $this->parseField($value);
            }
        }

        return $this->cleanText($text);
    }

    /**
     * Checks if a field is a meta field.
     *
     * @param string $field The field name
     * @return bool True if the field is a meta field, false otherwise
     */
    private function isMetaField(string $field): bool
    {
        return in_array($field, self::META_FIELDS, true);
    }

    /**
     * Parses a field value to a string.
     *
     * @param mixed $fieldVal The field value
     * @return string The parsed field value as a string
     */
    private function parseField(mixed $fieldVal): string
    {
        return (string) $fieldVal;
    }

    /**
     * Extracts text from a matrix field.
     *
     * @param object $entry The entry object
     * @param string $handle The handle of the matrix field
     * @param array<string> $include Array of block types to include
     * @return string The extracted and cleaned text
     */
    public function matrixCopy(object $entry, string $handle, array $include): string
    {
        if (!property_exists($entry, $handle) || !$entry->$handle) {
            return '';
        }

        $textBlocks = [];

        foreach ($entry->$handle->all() as $block) {
            if (!property_exists($block, 'type') || !property_exists($block->type, 'handle')) {
                continue;
            }

            $blockHandle = $block->type->handle;

            if (in_array($blockHandle, $include, true)) {
                $fields = $block->toArray();

                if (is_array($fields) && !empty($fields)) {
                    $textBlocks[] = $this->parseFields($fields, $include);
                }
            }
        }

        return $this->cleanText(implode(' ', array_filter($textBlocks)));
    }

    /**
     * Parses fields and extracts text.
     *
     * @param array<string,mixed> $fields Array of fields to parse
     * @param array<string> $fieldsToExtract Array of fields to extract
     * @param bool $related Whether to parse related entries
     * @return string The parsed and cleaned text
     */
    public function parseFields(array $fields, array $fieldsToExtract = self::DEFAULT_EXTRACT_FIELDS, bool $related = true): string
    {
        $text = '';

        foreach ($fields as $fieldHandle => $fieldValue) {
            if (in_array($fieldHandle, $fieldsToExtract, true)) {
                $text .= ' ' . $this->extractStringValue($fieldValue, $related);
            }
        }

        return $this->cleanText($text);
    }

    /**
     * Flattens an array into a string.
     *
     * @param array<mixed> $array The array to flatten
     * @return string The flattened array as a string
     */
    private function flattenArray(array $array): string
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                $result[] = $this->flattenArray($item);
            } elseif (is_string($item)) {
                $result[] = $item;
            } elseif ($item !== null) {
                $result[] = (string)$item;
            }
        }

        return implode(' ', $result);
    }

    /**
     * Extracts string value from field value.
     *
     * @param mixed $fieldValue The field value
     * @param bool $related Whether to parse related entries
     * @return string The extracted string
     */
    private function extractStringValue(mixed $fieldValue, bool $related): string
    {
        return match(true) {
            is_string($fieldValue) => $fieldValue,
            is_object($fieldValue) => match(true) {
                method_exists($fieldValue, '__toString') => (string)$fieldValue,
                $fieldValue instanceof \craft\elements\Entry && $related => $this->parseRelatedEntries($fieldValue),
                $fieldValue instanceof \craft\redactor\FieldData => (string)$fieldValue,
                default => ''
            },
            is_array($fieldValue) => $this->flattenArray($fieldValue),
            default => ''
        };
    }

    /**
     * Chunks text into smaller parts.
     *
     * @param string $content The text to chunk
     * @param int $maxSize The maximum size of each chunk
     * @return array<string> An array of text chunks
     */
    public function chunkText(string $content, int $maxSize = 3500): array
    {
        return $this->splitLongText($content, $maxSize);
    }

    /**
     * Fetches the spreadsheet content from the given asset and flattens it into a string.
     *
     * @param Asset $asset Craft asset
     * @return string The flattened spreadsheet content.
     * @throws Exception when the plugin is not installed or returns unexpected data
     */
    public function fetchAndFlattenSpreadsheet(Asset $asset): string
    {
        if (!Craft::$app->plugins->isPluginInstalled('spreadsheet-object')) {
            throw new Exception('The spreadsheet plugin is not installed.');
        }

        $spreadsheetContent = \wabisoft\spreadsheetobject\services\ProcessSpreadsheet::getArrayFromAsset($asset);

        if (!is_array($spreadsheetContent) || !isset($spreadsheetContent['rows']) || !is_array($spreadsheetContent['rows'])) {
            throw new Exception('Expected an array with rows from the spreadsheet plugin.');
        }

        return $this->flattenArray($spreadsheetContent['rows']);
    }

    /**
     * Splits long text into smaller parts.
     *
     * @param string $text The text to split
     * @param int $max The maximum length of each part
     * @return array<string> An array of text parts
     */
    public function splitLongText(string $text, int $max = 3500): array
    {
        $text = $this->cleanText($text);
        $parts = [];
        $prefix = '';

        while (mb_strlen($text) > 0) {
            if (mb_strlen($text) <= $max) {
                $parts[] = $prefix . $text;
                break;
            }

            $offset = -(mb_strlen($text) - $max);
            $cutPosition = mb_strrpos($text, ' ', $offset);

            if (false === $cutPosition) {
                $cutPosition = $max;
            }

            $parts[] = $prefix . mb_substr($text, 0, $cutPosition);
            $text = mb_substr($text, $cutPosition);
            $prefix = '… ';
        }

        return $parts;
    }

    /**
     * Cleans and formats text.
     *
     * @param string $text The text to clean
     * @return string The cleaned text
     */
    public function cleanText(string $text): string
    {
        // Decode HTML entities and handle special cases
        $text = html_entity_decode($text);
        $text = str_replace('&nbsp;', ' ', $text);
        $text = str_replace('<', ' <', $text);

        // Remove HTML tags
        $text = strip_tags($text);

        // Collapse whitespace
        return StringHelper::collapseWhitespace($text);
    }

    /**
     * Parses related entries and extracts text.
     *
     * @param object $relatedEntries The related entries object
     * @return string The parsed and cleaned text from related entries
     */
    private function parseRelatedEntries(object $relatedEntries): string
    {
        $textParts = [];

        foreach ($relatedEntries->all() as $item) {
            if (!property_exists($item, 'fieldValues')) {
                continue;
            }

            $textParts[] = $this->parseFields($item->fieldValues, self::DEFAULT_EXTRACT_FIELDS, false);
        }

        return $this->cleanText(implode(' ', $textParts));
    }
}