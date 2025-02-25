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
use craft\log\MonologTarget;
use Monolog\Formatter\LineFormatter;
use yii\log\Logger;

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

        // Register logger
        $this->registerLogTarget();

        // Register services
        $this->setComponents([
            'textExtraction' => TextExtraction::class,
            'entryHelpers' => EntryHelpers::class,
        ]);

        // Maintain backwards compatibility for any code that might still
        // be using the old service name directly
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Craft::$app->getDeprecator()->log(
                'Astuteo Search Transform',
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

    /**
     * Registers the log target for astuteo-search logging
     */
    private function registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'astuteo-search',
            'categories' => ['astuteo-search'],
            'level' => Logger::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "%datetime% %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }

    /**
     * Log an info message
     * @param mixed $message
     */
    public static function info($message): void
    {
        if (Craft::$app->config->general->devMode) {
            Craft::info($message, 'astuteo-search');
        }
    }

    /**
     * Log an error message
     * @param mixed $message
     */
    public static function error($message): void
    {
        Craft::error($message, 'astuteo-search');
    }

    /**
     * Log a warning message
     * @param mixed $message
     */
    public static function warning($message): void
    {
        Craft::warning($message, 'astuteo-search');
    }
}