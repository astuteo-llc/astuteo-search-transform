<?php

namespace astuteo\astuteosearchtransform\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\Entry;
use craft\base\ElementInterface;
use astuteo\astuteosearchtransform\AstuteoSearchTransform;
use craft\errors\InvalidFieldException;
use yii\base\InvalidConfigException;

/**
 * AssetHelpers provides methods for working with assets in entries.
 *
 * @package   AstuteoSearchTransform
 * @since     5.4.1
 */
class AssetHelpers extends Component
{
    /**
     * Get the first asset from an entry based on field handle(s).
     *
     * This method can be called in three ways:
     * 1. With just an entry that has a field directly: $entry->image
     * 2. With an entry and a string handle: ($entry, 'image')
     * 3. With an entry and an array of handles: ($entry, ['image', 'fallback'])
     *
     * @param ElementInterface $entry The entry to get the asset from
     * @param string|array|null $handle The field handle, array of handles, or null if using direct field
     * @return Asset|null The first asset found or null
     */
    public function getFirstAsset(ElementInterface $entry, string|array|null $handle = null): ?Asset
    {
        // Case 1: Direct field access ($entry->image)
        if ($handle === null && $entry instanceof Asset) {
            return $entry;
        }
        
        // Case 2: String handle
        if (is_string($handle)) {
            return $this->getAssetFromField($entry, $handle);
        }
        
        // Case 3: Array of handles
        if (is_array($handle)) {
            foreach ($handle as $fieldHandle) {
                $asset = $this->getAssetFromField($entry, $fieldHandle);
                if ($asset) {
                    return $asset;
                }
            }
        }
        
        return null;
    }

    /**
     * Get an asset from a specific field.
     *
     * @param ElementInterface $entry The entry to get the asset from
     * @param string $handle The field handle
     * @return Asset|null The asset or null if not found
     * @throws InvalidFieldException
     */
    private function getAssetFromField(ElementInterface $entry, string $handle): ?Asset
    {
        $field = $entry->getFieldValue($handle);
        
        if (!$field) {
            return null;
        }
        
        return match(true) {
            $field instanceof AssetQuery => $field->one(),
            $field instanceof Asset => $field,
            default => null
        };
    }
    
    /**
     * Get all assets from an entry based on field handle(s).
     *
     * @param ElementInterface $entry The entry to get assets from
     * @param string|array $handle The field handle or array of handles
     * @return array<Asset> An array of assets
     */
    public function getAllAssets(ElementInterface $entry, string|array $handle): array
    {
        // Case 1: String handle
        if (is_string($handle)) {
            return $this->getAssetsFromField($entry, $handle);
        }
        
        // Case 2: Array of handles
        if (is_array($handle)) {
            $assets = [];
            foreach ($handle as $fieldHandle) {
                $assets = array_merge($assets, $this->getAssetsFromField($entry, $fieldHandle));
            }
            return $assets;
        }
        
        return [];
    }

    /**
     * Get assets from a specific field.
     *
     * @param ElementInterface $entry The entry to get assets from
     * @param string $handle The field handle
     * @return array<Asset> An array of assets
     * @throws InvalidFieldException
     */
    private function getAssetsFromField(ElementInterface $entry, string $handle): array
    {
        $field = $entry->getFieldValue($handle);
        
        if (!$field) {
            return [];
        }
        
        return match(true) {
            $field instanceof AssetQuery => $field->all(),
            $field instanceof Asset => [$field],
            default => []
        };
    }

    /**
     * Get the URL of the first asset from an entry based on field handle(s).
     *
     * @param ElementInterface $entry The entry to get the asset URL from
     * @param string|array|null $handle The field handle, array of handles, or null if using direct field
     * @return string The URL of the first asset or an empty string
     * @throws InvalidConfigException
     */
    public function getFirstAssetUrl(ElementInterface $entry, string|array|null $handle = null): string
    {
        $asset = $this->getFirstAsset($entry, $handle);
        return $asset ? $asset->getUrl() : '';
    }

    /**
     * Get an array of asset URLs from an entry based on field handle(s).
     *
     * @param ElementInterface $entry The entry to get asset URLs from
     * @param string|array $handle The field handle or array of handles
     * @return array<string> An array of asset URLs
     * @throws InvalidConfigException
     */
    public function getAllAssetUrls(ElementInterface $entry, string|array $handle): array
    {
        $assets = $this->getAllAssets($entry, $handle);
        return array_filter(array_map(fn(Asset $asset): ?string => $asset->getUrl(), $assets));
    }

}
