<?php
declare(strict_types=1);

namespace astuteo\astuteosearchtransform\services;

use Craft;
use craft\base\Component;
use craft\helpers\StringHelper;

/**
 * This service class provides methods for extracting and transforming text
 * for search operations, primarily for use with Algolia search.
 *
 * @author    Astuteo
 * @package   AstuteoSearchTransform
 * @since     1.0.0
 */
class AstuteoSearchTransformService extends Component
{
    // Fields to extract text from
    const TEXT_FIELDS = ['text', 'plaintext'];

    public function extractMatrixText(object $matrix, string $handle, array $include = []): string
    {
        return $this->cleanText($this->matrixCopy($matrix, $handle, $include));
    }

    public function extractEntryText(object $entry, array $include = []): string
    {
        $fields = Craft::$app->getEntries()->getEntryById($entry->id)->fieldValues;
        $text = '';
        foreach ($fields as $field => $value) {
            if (in_array($field, $include)) {
                $text .= ' ' . $this->parseField($value);
            }
        }

        return $this->cleanText($text);
    }

    private function parseField(mixed $fieldVal): string
    {
        return (string) $fieldVal;
    }

    public function matrixCopy(object $entry, string $handle, array $include): string
    {
        $text = '';
        $textBlocks = [];
        if ($entry->$handle) {
            foreach ($entry->$handle->all() as $block) {
                $blockHandles = $block->type->handle;
                if (in_array($blockHandles, $include)) {
                    $fields = Craft::$app->getMatrix()->getBlockById($block->id)->fieldValues;
                    array_push($textBlocks, $this->parseFields($fields));
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

    public function parseFields(array $fields, bool $related = true): string
    {
        $text = '';
        foreach ($fields as $field) {
            $check = match (true) {
                isset($field?->elementType) => $field->elementType,
                is_object($field) => get_class($field),
                is_array($field) => 'array',
                default => 'string',
            };

            $text .= match ($check) {
                'craft\elements\Entry' => $related ? ' ' . $this->parseRelatedEntries($field) : '',
                'craft\redactor\FieldData', 'string' => ' ' . $field,
                'array' => ' ' . $this->flattenArray($field),
                default => '',
            };
        }
        return $this->cleanText($text);
    }

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

    private function flattenArray(array $array): string {
        $text = '';
        foreach ($array as $item) {
            $text .= is_array($item) ? ' ' . $this->flattenArray($item) : ' ' . $item;
        }
        return $this->cleanText($text);
    }

    private function parseRelatedEntries(object $relatedEntries): string
    {
        $text = '';
        foreach ($relatedEntries->all() as $item) {
            $fields = Craft::$app->getEntries()->getEntryById($item->id)->fieldValues;
            $text = $this->parseFields($fields, false);
        }
        return $this->cleanText($text);
    }
}
