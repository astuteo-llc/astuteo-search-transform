# Astuteo Search Transform utility for Craft CMS

A utility library for extracting and transforming text content from Craft CMS entries and fields, primarily designed for search operations such as Algolia integration.

## Requirements

This package requires Craft CMS 4.0 or later (Craft 5 compatible)

## Installation Options

This package can be used in two ways:

### 1. As a standalone utility (recommended for single projects)

```bash
composer require astuteollc/astuteo-search-transform
```

No need to install it as a plugin in the Control Panel. Just require it via Composer and use the service classes directly.

### 2. As a full plugin (recommended for multiple projects)

```bash
composer require astuteollc/astuteo-search-transform
```

Then in the Control Panel, go to Settings → Plugins and click the "Install" button for Astuteo Search Transform.

## Usage

### Flexible Usage Pattern

The code is designed to work whether or not you've installed it as a plugin. Here's an easy pattern that works in both scenarios:

```php
use astuteo\astuteosearchtransform\AstuteoSearchTransform;
use astuteo\astuteosearchtransform\services\TextExtraction;

class MyHelper
{
    private $textExtraction = null;
    
    private function getTextExtraction()
    {
        if ($this->textExtraction === null) {
            // Try to get from plugin if installed
            $plugin = class_exists(AstuteoSearchTransform::class) ? AstuteoSearchTransform::getInstance() : null;
            if ($plugin && property_exists($plugin, 'textExtraction')) {
                $this->textExtraction = $plugin->textExtraction;
            } else {
                // Fallback - create direct instance
                $this->textExtraction = new TextExtraction();
            }
        }
        return $this->textExtraction;
    }
    
    public function someMethod($entry)
    {
        return $this->getTextExtraction()->extractEntryText($entry, ['body', 'summary']);
    }
}
```

### TextExtraction Service

This service handles extracting and transforming text content from entries and fields.

```php
// Access the TextExtraction service
$textExtraction = \astuteo\astuteosearchtransform\AstuteoSearchTransform::getInstance()->textExtraction;
```

#### Available Methods:

##### `extractEntryText(object $entry, array $include = []): string`

Extracts text content from an entry, optionally limiting to specific fields.

```php
// Extract text from all fields in an entry
$text = $textExtraction->extractEntryText($entry);

// Extract text from specific fields only
$text = $textExtraction->extractEntryText($entry, ['body', 'summary', 'metaDescription']);
```

##### `extractMatrixText(object $matrix, string $handle, array $include = []): string`

Extracts text from a matrix field, optionally limiting to specific block types.

```php
// Extract text from a matrix field
$text = $textExtraction->extractMatrixText($entry, 'contentBlocks', ['text', 'heading']);
```

##### `parseFields(array $fields, array $fieldsToExtract = ['text','heading'], bool $related = true): string`

Parses field data and extracts text content.

```php
// Parse fields from an array
$fields = $entry->toArray();
$text = $textExtraction->parseFields($fields, ['body', 'summary']);
```

##### `chunkText(string $content, int $maxSize = 3500): array`

Splits text into chunks of a specified maximum size, useful for APIs with text limits.

```php
// Split long content into chunks of 3500 characters
$chunks = $textExtraction->chunkText($longText);

// Split into smaller chunks
$chunks = $textExtraction->chunkText($longText, 1000);
```

##### `splitLongText(string $text, int $max = 3500): array`

Similar to chunkText, splits text into smaller parts.

```php
// Split text into parts
$parts = $textExtraction->splitLongText($longText);
```

##### `fetchAndFlattenSpreadsheet(craft\elements\Asset $asset): string`

Extracts content from a spreadsheet asset and flattens it into a string.

```php
// Get text content from a spreadsheet
$spreadsheetText = $textExtraction->fetchAndFlattenSpreadsheet($spreadsheetAsset);
```

### EntryHelpers Service

This service provides utility methods for working with entry fields, particularly relationships and assets.

```php
// Access the EntryHelpers service
$entryHelpers = \astuteo\astuteosearchtransform\AstuteoSearchTransform::getInstance()->entryHelpers;
```

#### Available Methods:

##### `getRelatedTitles(?Entry $entry, string $handle): array`

Gets an array of titles from a relationship field (entries or categories).

```php
// Get titles from related entries
$titles = $entryHelpers->getRelatedTitles($entry, 'relatedArticles'); 
// Returns: ['Article 1', 'Article 2']
```

##### `getImage(Entry $entry, string $fieldHandle): string`

Gets the URL of the first image in an asset field.

```php
// Get the URL of the main image
$imageUrl = $entryHelpers->getImage($entry, 'featuredImage');
// Returns: 'https://example.com/uploads/image.jpg'
```

##### `getImages(Entry $entry, string $fieldHandle): array`

Gets an array of URLs for all images in an asset field.

```php
// Get URLs of all gallery images
$imageUrls = $entryHelpers->getImages($entry, 'galleryImages');
// Returns: ['https://example.com/image1.jpg', 'https://example.com/image2.jpg']
```

## Example: Preparing Entry Data for Algolia

Here's a practical example of using the plugin to prepare entry data for Algolia indexing:

```php
use astuteo\astuteosearchtransform\AstuteoSearchTransform;

function prepareEntryForAlgolia($entry)
{
    $textExtraction = AstuteoSearchTransform::getInstance()->textExtraction;
    $entryHelpers = AstuteoSearchTransform::getInstance()->entryHelpers;

    return [
        'objectID' => $entry->id,
        'title' => $entry->title,
        'url' => $entry->url,
        'content' => $textExtraction->extractEntryText($entry, ['body', 'summary']),
        'categories' => $entryHelpers->getRelatedTitles($entry, 'categories'),
        'imageUrl' => $entryHelpers->getImage($entry, 'featuredImage'),
        'gallery' => $entryHelpers->getImages($entry, 'galleryImages'),
        'lastUpdated' => $entry->dateUpdated->format('c')
    ];
}
```

## Migrating from Static Methods

Earlier versions of the plugin used static methods in the EntryHelpers class. These are still supported but deprecated if the plugin is installed.

Old way (deprecated):
```php
use astuteo\astuteosearchtransform\services\EntryHelpers;

$titles = EntryHelpers::getRelatedTitlesFromField($entry, 'relatedArticles');
$imageUrl = EntryHelpers::getFirstImage($entry, 'featuredImage');
$imageUrls = EntryHelpers::getImageUrls($entry, 'galleryImages');
```

New way:
```php
$plugin = \astuteo\astuteosearchtransform\AstuteoSearchTransform::getInstance();
$titles = $plugin->entryHelpers->getRelatedTitles($entry, 'relatedArticles');
$imageUrl = $plugin->entryHelpers->getImage($entry, 'featuredImage');
$imageUrls = $plugin->entryHelpers->getImages($entry, 'galleryImages');
```

## Brought to you by [Astuteo](https://astuteo.com)