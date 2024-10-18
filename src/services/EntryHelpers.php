<?php

namespace astuteo\astuteosearchtransform\services;

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\AssetQuery;
use craft\elements\Entry;

/**
 * EntryHelpers provides methods for extracting and transforming text
 * for search operations, primarily for use with Algolia search.
 *
 * @package   AstuteoSearchTransform
 * @since     5.3.0
 */
class EntryHelpers
{
    /**
     * Get an array of titles from a given relationship field (categories or entries).
     *
     * @param Entry|null $entry The entry to get related elements from
     * @param string $handle The field handle
     * @return array An array of related element titles
     */
    public static function getRelatedTitlesFromField(?Entry $entry, string $handle): array
    {
        if (!$entry) {
            return [];
        }

        $field = $entry->getFieldValue($handle);
        if (!$field) {
            return [];
        }

        if ($field instanceof CategoryQuery || $field instanceof EntryQuery) {
            return array_map(function (ElementInterface $element) {
                return $element->title;
            }, $field->all() ?? []);
        } elseif ($field instanceof Category || $field instanceof Entry) {
            return [$field->title];
        }

        return [];
    }

    /**
     * Get the URL of the first image from a given field.
     *
     * @param Entry $entry The entry to get the image from
     * @param string $fieldHandle The field handle
     * @return string The URL of the first image or an empty string
     */
    public static function getFirstImage(Entry $entry, string $fieldHandle): string
    {
        $field = $entry->getFieldValue($fieldHandle);

        if (!$field) {
            return '';
        }

        if ($field instanceof AssetQuery) {
            $firstImage = $field->kind('image')->one();
        } elseif ($field instanceof Asset && $field->kind === 'image') {
            $firstImage = $field;
        } else {
            $firstImage = null;
        }

        return $firstImage ? $firstImage->getUrl() : '';
    }

    /**
     * Get an array of image URLs from a given field.
     *
     * @param Entry $entry The entry to get images from
     * @param string $fieldHandle The field handle
     * @return array An array of image URLs
     */
    public static function getImageUrls(Entry $entry, string $fieldHandle): array
    {
        $field = $entry->getFieldValue($fieldHandle);

        if (!$field) {
            return [];
        }

        if ($field instanceof AssetQuery) {
            $assets = $field->kind('image')->all();
        } elseif ($field instanceof Asset && $field->kind === 'image') {
            $assets = [$field];
        } else {
            $assets = [];
        }

        return array_map(fn(Asset $asset) => $asset->getUrl(), $assets);
    }
}