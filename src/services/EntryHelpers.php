<?php

namespace astuteo\astuteosearchtransform\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AssetQuery;
use craft\elements\db\CategoryQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\base\ElementInterface;
use astuteo\astuteosearchtransform\AstuteoSearchTransform;

/**
 * EntryHelpers provides methods for extracting and transforming text
 * for search operations, primarily for use with Algolia search.
 *
 * @package   AstuteoSearchTransform
 * @since     5.4.0
 */
class EntryHelpers extends Component
{
    /**
     * Get an array of titles from a given relationship field (categories or entries).
     *
     * @param Entry|null $entry The entry to get related elements from
     * @param string|array<string> $handle The field handle or array of field handles
     * @return array<string> An array of related element titles
     */
    public function getRelatedTitles(
        ?Entry $entry,
        string|array $handle
    ): array
    {
        if (!$entry) {
            return [];
        }

        // If handle is a string, process it directly
        if (is_string($handle)) {
            return $this->processRelatedField($entry, $handle);
        }

        // If handle is an array, process each handle and merge the results
        if (is_array($handle)) {
            $titles = [];
            foreach ($handle as $fieldHandle) {
                $titles = array_merge($titles, $this->processRelatedField($entry, $fieldHandle));
            }
            return $titles;
        }

        return [];
    }

    /**
     * Process a single related field to extract titles.
     *
     * @param Entry $entry
     * @param string $handle
     * @return array<string> An array of related element titles
     */
    private function processRelatedField(
        Entry $entry,
        string $handle
    ): array
    {
        $field = $entry->getFieldValue($handle);
        if (!$field) {
            return [];
        }

        return match(true) {
            $field instanceof CategoryQuery || $field instanceof EntryQuery
            => array_map(fn(ElementInterface $e): string => (string)$e->title, $field->all() ?: []),
            $field instanceof Category || $field instanceof Entry
            => [(string)$field->title],
            default => []
        };
    }

    /**
     * Get the URL of the first image from a given field.
     *
     * @param Entry $entry
     * @param string $fieldHandle
     * @return string The URL of the first image or an empty string
     */
    public function getImage(
        Entry $entry,
        string $fieldHandle
    ): string
    {
        $field = $entry->getFieldValue($fieldHandle);

        if (!$field) {
            return '';
        }

        return match(true) {
            $field instanceof AssetQuery => $field->kind('image')->one()?->getUrl() ?: '',
            $field instanceof Asset && $field->kind === 'image' => $field->getUrl() ?: '',
            default => ''
        };
    }

    /**
     * Get an array of image URLs from a given field.
     *
     * @param Entry $entry
     * @param string $fieldHandle
     * @return array<string> An array of image URLs
     */
    public function getImages(
        Entry $entry,
        string $fieldHandle
    ): array
    {
        $field = $entry->getFieldValue($fieldHandle);

        if (!$field) {
            return [];
        }

        $assets = match(true) {
            $field instanceof AssetQuery => $field->kind('image')->all(),
            $field instanceof Asset && $field->kind === 'image' => [$field],
            default => []
        };

        return array_filter(array_map(fn(Asset $asset): ?string => $asset->getUrl(), $assets));
    }

    // Static methods for backward compatibility

    /**
     * @deprecated in 5.3.1 Use instance method getRelatedTitles() instead
     */
    public static function getRelatedTitlesFromField(
        ?Entry $entry,
        string $handle
    ): array
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getRelatedTitlesFromField() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getRelatedTitles() instead.');
        return AstuteoSearchTransform::getInstance()->entryHelpers->getRelatedTitles($entry, $handle);
    }

    /**
     * @deprecated in 5.3.1 Use instance method getImage() instead
     */
    public static function getFirstImage(
        Entry $entry,
        string $fieldHandle
    ): string
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getFirstImage() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getImage() instead.');
        return AstuteoSearchTransform::getInstance()->entryHelpers->getImage($entry, $fieldHandle);
    }

    /**
     * @deprecated in 5.3.1 Use instance method getImages() instead
     */
    public static function getImageUrls(
        Entry $entry,
        string $fieldHandle
    ): array
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getImageUrls() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getImages() instead.');
        return AstuteoSearchTransform::getInstance()->entryHelpers->getImages($entry, $fieldHandle);
    }
}