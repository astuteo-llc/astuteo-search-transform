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
     * @param string $handle The field handle
     * @return array An array of related element titles
     */
    public function getRelatedTitles(?Entry $entry, string $handle): array
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
    public function getImage(Entry $entry, string $fieldHandle): string
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
    public function getImages(Entry $entry, string $fieldHandle): array
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

    // Static methods for backward compatibility - keeping original method names

    /**
     * Get an array of titles from a given relationship field (categories or entries).
     *
     * @param Entry|null $entry The entry to get related elements from
     * @param string $handle The field handle
     * @return array An array of related element titles
     * @deprecated in 5.3.1 Use instance method getRelatedTitles() instead
     */
    public static function getRelatedTitlesFromField(?Entry $entry, string $handle): array
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getRelatedTitlesFromField() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getRelatedTitles() instead.');
        return (new self())->getRelatedTitles($entry, $handle);
    }

    /**
     * Get the URL of the first image from a given field.
     *
     * @param Entry $entry The entry to get the image from
     * @param string $fieldHandle The field handle
     * @return string The URL of the first image or an empty string
     * @deprecated in 5.3.1 Use instance method getImage() instead
     */
    public static function getFirstImage(Entry $entry, string $fieldHandle): string
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getFirstImage() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getImage() instead.');
        return (new self())->getImage($entry, $fieldHandle);
    }

    /**
     * Get an array of image URLs from a given field.
     *
     * @param Entry $entry The entry to get images from
     * @param string $fieldHandle The field handle
     * @return array An array of image URLs
     * @deprecated in 5.3.1 Use instance method getImages() instead
     */
    public static function getImageUrls(Entry $entry, string $fieldHandle): array
    {
        AstuteoSearchTransform::info('Using static EntryHelpers::getImageUrls() has been deprecated. Use AstuteoSearchTransform::getInstance()->entryHelpers->getImages() instead.');
        return (new self())->getImages($entry, $fieldHandle);
    }
}