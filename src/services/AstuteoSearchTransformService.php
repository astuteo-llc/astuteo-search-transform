<?php
declare(strict_types=1);

namespace astuteo\astuteosearchtransform\services;

use Craft;
use craft\base\Component;
use craft\helpers\StringHelper;

/**
 * AstuteoSearchTransformService provides methods for extracting and transforming text
 * for search operations, primarily for use with Algolia search.
 *
 * @package   AstuteoSearchTransform
 * @since     2.0.0
 */
class AstuteoSearchTransformService extends Component
{
    /**
     * Extracts text from a matrix field.
     *
     * @param object $matrix The matrix field object
     * @param string $handle The handle of the matrix field
     * @param array $include Optional array of block types to include
     * @return string The extracted and cleaned text
     */
    public function extractMatrixText(object $matrix, string $handle, array $include = []): string
    {
        return $this->cleanText($this->matrixCopy($matrix, $handle, $include));
    }

    /**
     * Extracts text from an entry.
     *
     * @param object $entry The entry object
     * @param array $include Optional array of fields to include
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

            if (empty($include) || in_array($field, $include)) {
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
        $metaFields = [
            'id', 'uid', 'dateCreated', 'dateUpdated', 'siteId', 'enabled',
            'status', 'slug', 'uri', 'authorId', 'archived', 'sectionId', 'typeId',
            'revisionId', 'postDate', 'expiryDate'
        ];

        return in_array($field, $metaFields);
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
     * @param array $include Array of block types to include
     * @return string The extracted and cleaned text
     */
    public function matrixCopy(object $entry, string $handle, array $include): string
    {
        $text = '';
        $textBlocks = [];

        if ($entry->$handle) {
            foreach ($entry->$handle->all() as $block) {
                $blockHandle = $block->type->handle;

                if (in_array($blockHandle, $include)) {
                    $fields = $block->toArray();

                    if (is_array($fields) && !empty($fields)) {

                        $textBlocks[] = $this->parseFields($fields, $include);
                    }
                }
            }
        }

        foreach ($textBlocks as $textBlock) {
            if ($textBlock) {
                $text .= ' ' . $textBlock;
            }
        }

        return $this->cleanText($text);
    }

    /**
     * Parses fields and extracts text.
     * @DEV: I'd like to refactor this to be more flexible and allow for more complex field parsing
     * in not requre passing what are essentially entry types. As-is, this will break Craft 4
     * sites when we upgrade
     *
     * @param array $fields Array of fields to parse
     * @param array $fieldsToExtract Array of fields to extract
     * @param bool $related Whether to parse related entries
     * @return string The parsed and cleaned text
     */
    public function parseFields(array $fields, array $fieldsToExtract = ['text','heading'], bool $related = true): string
    {
        $text = '';
        foreach ($fields as $fieldHandle => $fieldValue) {
            if (in_array($fieldHandle, $fieldsToExtract)) {
                $text .= ' ' . $this->extractStringValue($fieldValue, $related);
            }
        }

        return $this->cleanText($text);
    }


    /**
     * @param array $array
     * @return string
     */
    private function flattenArray(array $array): string
    {
        $text = '';
        foreach ($array as $item) {
            if (is_array($item)) {
                $text .= ' ' . $this->flattenArray($item);
            } elseif (is_string($item)) {
                $text .= ' ' . $item;
            }
        }
        return $text;
    }


    /**
     * @param $fieldValue
     * @param bool $related
     * @return string
     */
    private function extractStringValue($fieldValue, bool $related): string
    {
        if (is_string($fieldValue)) {
            return $fieldValue;
        }

        if (is_object($fieldValue)) {
            if (method_exists($fieldValue, '__toString')) {
                return (string)$fieldValue;
            }

            if ($fieldValue instanceof \craft\elements\Entry && $related) {
                return $this->parseRelatedEntries($fieldValue);
            }

            if ($fieldValue instanceof \craft\redactor\FieldData) {
                return (string)$fieldValue;
            }
        }

        if (is_array($fieldValue)) {
            return $this->flattenArray($fieldValue);
        }

        return '';
    }

    /**
     * Chunks text into smaller parts.
     *
     * @param string $content The text to chunk
     * @param int $maxSize The maximum size of each chunk
     * @return array An array of text chunks
     */
    public function chunkText(string $content, int $maxSize = 3500): array
    {
        $parts = [];
        $prefix = '';
        $content = $this->cleanText($content);

        do {
            if (mb_strlen($content) <= $maxSize) {
                $parts[] = $prefix . $content;
                $content = '';
            } else {
                $offset = -(mb_strlen($content) - $maxSize);
                $cut_at_position = mb_strrpos($content, ' ', $offset);
                if (false === $cut_at_position) {
                    $cut_at_position = $maxSize;
                }
                $parts[] = $prefix . mb_substr($content, 0, $cut_at_position);
                $content = mb_substr($content, $cut_at_position);
                $prefix = '… ';
            }
        } while ($content !== '');

        return $parts;
    }

    /**
     * Fetches the spreadsheet content from the given asset and flattens it into a string.
     *
     * @param craft\elements\Asset $asset Craft asset
     * @return string The flattened spreadsheet content.
     */
    public function fetchAndFlattenSpreadsheet(craft\elements\Asset $asset): string
    {
        if (!Craft::$app->plugins->isPluginInstalled('spreadsheet-object')) {
            throw new \Exception('The spreadsheet plugin is not installed.');
        }
        $spreadsheetContent = \wabisoft\spreadsheetobject\services\ProcessSpreadsheet::getArrayFromAsset($asset);
        if (!is_array($spreadsheetContent)) {
            throw new \Exception('Expected an array from the spreadsheet plugin.');
        }
        return $this->flattenArray($spreadsheetContent['rows']);
    }

    /**
     * Splits long text into smaller parts.
     *
     * @param string $text The text to split
     * @param int $max The maximum length of each part
     * @return array An array of text parts
     */
    public function splitLongText(string $text, int $max = 3500): array
    {
        $text = $this->cleanText($text);
        $parts = [];
        $prefix = '';

        while (strlen($text) > 0) {
            if (strlen($text) <= $max) {
                $parts[] = $prefix . $text;
                break;
            }
            $offset = -(strlen($text) - $max);
            $cut_at_position = strrpos($text, ' ', $offset);
            if (false === $cut_at_position) {
                $cut_at_position = $max;
            }
            $parts[] = $prefix . substr($text, 0, $cut_at_position);
            $text = substr($text, $cut_at_position);
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
    private function cleanText(string $text): string
    {
        //@NOTE: I'm not sure if we want to encode this, but testing it
        $text = html_entity_decode($text); // Decode HTML entities
        $text = str_replace('&nbsp;', ' ', $text); // Convert non-breaking spaces to regular spaces
        $text = str_replace('<', ' <', $text); // Add space before '<' to ensure spaces between tags

        $text = strip_tags($text); // Remove HTML tags

        $text = StringHelper::collapseWhitespace($text); // Collapse whitespace

        return $text;
    }

    /**
     * Flattens an array into a string.
     *
     * @param array $array The array to flatten
     * @return string The flattened array as a string
     */


    /**
     * Parses related entries and extracts text.
     *
     * @param object $relatedEntries The related entries object
     * @return string The parsed and cleaned text from related entries
     */
    private function parseRelatedEntries(object $relatedEntries): string
    {
        $text = '';
        foreach ($relatedEntries->all() as $item) {
            // Correctly access the related entry's field values
            $fields = $item->fieldValues;
            $text .= $this->parseFields($fields, false);
        }
        return $this->cleanText($text);
    }
}
