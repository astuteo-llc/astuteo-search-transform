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
use craft\helpers\StringHelper;


/**
 * AstuteoSearchTransformService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Astuteo
 * @package   AstuteoSearchTransform
 * @since     1.0.0
 */
class AstuteoSearchTransformService extends Component
{

    // Constants
    // Fields to extract text from
    const TEXT_FIELDS = [
        'text',
        'plaintext'
    ];

    // Public Methods
    // =========================================================================

    public function extractMatrixText($matrix, $handle, $include = [])
    {
        return $this->cleanText($this->matrixCopy($matrix, $handle, $include));
    }

    public function extractEntryText($entry, $include = [])
    {
        $fields = Craft::$app->getEntries()->getEntryById($entry->id)->fieldValues;
        $text = '';
        foreach ( $fields as $field => $value) {
            switch ($field) {
                case in_array($field, $include);
                    $text = ' ' . $text . ' ' . $this->parseField($value);
            }
        }

        return $this->cleanText($text);
    }

    private function parseField($fieldVal) {
        return $fieldVal;
    }

    public function matrixCopy($entry, $handle, $include): string
    {
        $text = '';
        $textBlocks = [];
        $siteId = $entry->siteId;

        if ($entry->$handle) {
            foreach ($entry->$handle->all() as $block) {
                $blockHandles = $block->type->handle;
                if (in_array($blockHandles, $include)) {
                    $matrixBlock = Craft::$app->getMatrix()->getBlockById($block->id, $siteId);
                    if ($matrixBlock !== null) {
                        $fields = $matrixBlock->fieldValues;
                        $textBlocks[] = $this->parseFields($fields);
                    } else {
                        Craft::error("Matrix block with ID {$block->id} not found for site ID {$siteId}.", __METHOD__);
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




    // Extract Direct Entry Text Values
    private function parseEntryFields($fields, array $include) {
        return $this->cleanText( $this->parseFields($fields));
    }

    // Returns values from text fields. Accessed by Entries
    // Categories, Matrix blocks
    public function parseFields(array $fields, bool $related = true): string
    {
        $text = '';
        foreach ($fields as $field) {
            // Replace match with if/elseif for determining $check
            if (isset($field->elementType)) {
                $check = $field->elementType;
            } elseif (is_object($field)) {
                $check = get_class($field);
            } elseif (is_array($field)) {
                $check = 'array';
            } else {
                $check = 'string';
            }

            // Replace the second match with switch
            switch ($check) {
                case 'craft\elements\Entry':
                    $text .= $related ? ' ' . $this->parseRelatedEntries($field) : '';
                    break;
                case 'craft\redactor\FieldData':
                case 'string':
                    $text .= ' ' . $field;
                    break;
                case 'array':
                    $text .= ' ' . $this->flattenArray($field);
                    break;
                default:
                    break;
            }
        }
        return $this->cleanText($text);
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

    // Pull out related entry text fields
    private function parseRelatedEntries($relatedEntries) {
        $text = '';
        foreach ($relatedEntries->all() as $item) {
            $fields = Craft::$app->getEntries()->getEntryById($item->id)->fieldValues;
            $text = $this->parseFields($fields, false);
        }
        return $this->cleanText($text);
    }

}
