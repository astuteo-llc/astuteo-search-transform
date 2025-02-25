<?php
/**
 * Astuteo Search Transform plugin for Craft CMS
 *
 * Helper to modify text for search operations, primarily for Algolia search.
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 * @package   AstuteoSearchTransform
 */
namespace astuteo\astuteosearchtransform;

use astuteo\astuteosearchtransform\services\TextExtraction;
use astuteo\astuteosearchtransform\services\EntryHelpers;
use astuteo\astuteosearchtransform\services\AstuteoSearchTransformService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

/**
 * Class AstuteoSearchTransform
 *
 * @property TextExtraction $textExtraction The text extraction service
 * @property EntryHelpers $entryHelpers The entry helpers service
 * @package astuteo\astuteosearchtransform
 */
class AstuteoSearchTransform extends Plugin
{
    /**
     * @var AstuteoSearchTransform
     */
    public static $plugin;

    /**
     * @var string
     */
    public string $schemaVersion = '5.4.0';

    /**
     * Initializes the plugin.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register services
        $this->setComponents([
            'textExtraction' => TextExtraction::class,
            'entryHelpers' => EntryHelpers::class,
        ]);

        // Maintain backwards compatibility for any code that might still
        // be using the old service name directly
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Craft::$app->getDeprecator()->log(
                'AstuteoSearchTransformService',
                'Using AstuteoSearchTransformService directly has been deprecated. Use AstuteoSearchTransform::getInstance()->textExtraction instead.'
            );
        }

        Craft::info(
            Craft::t(
                'astuteo-search-transform',
                '{name} plugin initialized',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }
}