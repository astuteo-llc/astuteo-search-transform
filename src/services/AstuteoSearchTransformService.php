<?php
/**
 * Astuteo Search Transform plugin for Craft CMS 4.x
 *
 * Helper to modify text on projects Astuteo is using Algolia search on.
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 */
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

    /**
     * Extracts the text from a given matrix field.
     *
     * @param  mixed  $matrix
     * @param  string  $handle
     * @param  array  $include
     * @return string  The extracted and cleaned text.
     */
    public function extractMatrixText($matrix, $handle, $include = []): string
    {
        // Validation
        if (!is_object($matrix)) {
            throw new \InvalidArgumentException('Invalid matrix or handle.');
        }
        return $this->cleanText($this->matrixCopy($matrix, $handle, $include));
    }

    /**
     * Extracts the text from a given entry.
     *
     * @param  mixed  $entry
     * @param  array  $include
     * @return string  The extracted and cleaned text.
     */
    public function extractEntryText($entry, $include = []): string
    {
        $fields = Craft::$app->getEntries()->getEntryById($entry->id)->fieldValues;
        $text = '';
        foreach ($fields as $field => $value) {
            switch ($field) {
                case in_array($field, $include):
                    $text = ' ' . $text . ' ' . $this->parseField($value);
            }
        }

        return $this->cleanText($text);
    }

    // Dummy function to be filled with custom parsing logic
    private function parseField($fieldVal)
    {
        return $fieldVal;
    }

    // Extract text from a given matrix field
    public function matrixCopy($entry, $handle, $include): string
    {
        $text = '';
        $textBlocks = [];
        if ($entry->$handle) {
            foreach ($entry->$handle->all() as $block) {
                $blockHandles = $block->type->handle;
                switch ($blockHandles) {
                    case in_array($blockHandles, $include):
                        $fields = Craft::$app->getMatrix()->getBlockById($block->id)->fieldValues;
                        array_push($textBlocks, $this->parseFields($fields));
                        break;
                }
            }
        }
        foreach ($textBlocks as $textBlock) {
            if ($textBlock) {
                $text = $text . ' ' . $textBlock;
            }
        }
        return $this->cleanText($text);
    }

    // Extract direct entry text values
    private function parseEntryFields($fields, array $include): string
    {
        return $this->cleanText($this->parseFields($fields));
    }

    // Parse fields of a given entry
    public function parseFields($fields, $related = true): string
    {
        $text = '';
        foreach ($fields as $field) {
            if (isset($field->elementType)) {
                $check = $field
                    ->elementType;
            } elseif (is_object($field)) {
                $check = get_class($field);
            } else {
                $check = 'string';
            }
            switch ($check) {
                case 'craft\elements\Entry':
                    if ($related) {
                        $text = $this->parseRelatedEntries($field);
                    }
                    break;
                case 'craft\redactor\FieldData':
                    $text = $text . ' ' . $field;
                    break;
                case 'string':
                    $text = $text . ' ' . $field;
                default:
            }
        }
        return $this->cleanText($text);
    }

    /**
     * Breaks a string into chunks of a specific size.
     *
     * @param  string  $content  The string to be chunked.
     * @param  int  $maxSize  The maximum size of each chunk.
     * @return array  An array of string chunks.
     */
    public function chunkText($content, $maxSize = 3500) {
        // Validation
        if (!is_int($maxSize) || $maxSize <= 0) {
            throw new \InvalidArgumentException('Max size should be a positive integer.');
        }
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
     * Cleans a text string by removing HTML tags and excess whitespace.
     *
     * @param  string  $text  The text string to be cleaned.
     * @return string  The cleaned text string.
     */
    private function cleanText(string $text): string
    {
        //@NOTE: I'm not sure if we want to encode this, but testing it
        $text = html_entity_decode($text); // Decode HTML entities
        $text = str_replace('&nbsp;', ' ', $text); // Convert non-breaking spaces to regular spaces
        $text = str_replace('<', ' <', $text); // Add space before '<' to ensure spaces between tags

        // Consider using a library like HTMLPurifier for better HTML tag removal
        $text = strip_tags($text);

        // Collapse whitespace
        $text = StringHelper::collapseWhitespace($text);

        return $text;
    }

    /**
     * Parses related entries and extracts their text.
     *
     * @param  mixed  $relatedEntries
     * @return string  The extracted and cleaned text.
     */
    private function parseRelatedEntries($relatedEntries): string
    {
        $text = '';
        foreach ($relatedEntries->all() as $item) {
            $fields = Craft::$app->getEntries()->getEntryById($item->id)
                ->fieldValues;
            $text = $this->parseFields($fields, false);
        }
        return $this->cleanText($text);
    }
}
