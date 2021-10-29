<?php
/**
 * Astuteo Search Transform plugin for Craft CMS 3.x
 *
 * Helper to modify text on projects Astuteo is using Algolia search on.
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 */
namespace astuteo\astuteosearchtransform\services;

use Craft;
use craft\base\Component;

/**
 * @author    Astuteo
 * @package   AstuteoSearchTransform
 * @since     1.0.0
 */
class AstuteoSearchTransformService extends Component
{
    // Constant
    // Fields to extract text from
    const TEXT_FIELDS = ['text', 'plaintext'];
    public function extractMatrixText($matrix, $handle, $include = [])
    {
        return $this->cleanText($this->matrixCopy($matrix, $handle, $include));
    }

    public function extractEntryText($entry, $include = [])
    {
        $fields = Craft::$app->getEntries()->getEntryById($entry->id)
            ->fieldValues;
        $text = '';
        foreach ($fields as $field => $value) {
            switch ($field) {
                case in_array($field, $include):
                    $text = ' ' . $text . ' ' . $this->parseField($value);
            }
        }

        return $this->cleanText($text);
    }

    private function parseField($fieldVal)
    {
        return $fieldVal;
    }

    public function matrixCopy($entry, $handle, $include)
    {
        $text = '';
        $textBlocks = [];
        if ($entry->$handle) {
            foreach ($entry->$handle->all() as $block) {
                $blockHandles = $block->type->handle;
                switch ($blockHandles) {
                    case in_array($blockHandles, $include):
                        $fields = Craft::$app
                            ->getMatrix()
                            ->getBlockById($block->id)->fieldValues;
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

    // Extract Direct Entry Text Values
    private function parseEntryFields($fields, array $include)
    {
        return $this->cleanText($this->parseFields($fields));
    }

    public function parseFields($fields, $related = true)
    {
        $text = '';
        foreach ($fields as $field) {
            if (isset($field->elementType)) {
                $check = $field->elementType;
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

    private function cleanText(string $text)
    {
        return strip_tags(trim($text));
    }

    private function parseRelatedEntries($relatedEntries)
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
