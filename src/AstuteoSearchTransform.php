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

use astuteo\astuteosearchtransform\services\DevHelpers;
use astuteo\astuteosearchtransform\services\TextExtraction;
use astuteo\astuteosearchtransform\services\EntryHelpers;
use astuteo\astuteosearchtransform\services\AssetHelpers;
use astuteo\astuteosearchtransform\services\AstuteoSearchTransformService;
use astuteo\astuteosearchtransform\variables\PreviewVariables;

use Craft;
use craft\base\Plugin;
use craft\log\MonologTarget;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;


/**
 * Class AstuteoSearchTransform
 *
 * @property TextExtraction $textExtraction The text extraction service
 * @property EntryHelpers $entryHelpers The entry helpers service
 * @property-read DevHelpers $devHelpers
 * @property AssetHelpers $assetHelpers The asset helpers service
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
    public string $schemaVersion = '6.0.0';
    
    /**
     * Log category used for all plugin logging
     */
    public const LOG_CATEGORY = 'astuteo-search';

    /**
     * @var TextExtraction|null
     */
    public ?TextExtraction $textExtraction = null;
    
    /**
     * @var EntryHelpers|null
     */
    public ?EntryHelpers $entryHelpers = null;

    /**
     * @var AssetHelpers|null
     */
    public ?AssetHelpers $assetHelpers = null;

    /**
     * Initializes the plugin.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register logger
        $this->registerLogTarget();
        $this->registerVariables();

        // Register services
        $this->setComponents([
            'textExtraction' => TextExtraction::class,
            'entryHelpers' => EntryHelpers::class,
            'devHelpers' => DevHelpers::class,
            'assetHelpers' => AssetHelpers::class,
        ]);

        // Log successful initialization
        self::info(
            Craft::t(
                'astuteo-search-transform',
                '{name} plugin initialized',
                ['name' => $this->name]
            )
        );

        Craft::info(
            Craft::t(
                'astuteo-search-transform',
                '{name} plugin initialized',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    private function registerVariables(): void
    {
        // Register Craft Variable
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('searchPreview', PreviewVariables::class);
            }
        );
    }

    /**
     * Registers the log target for astuteo-search logging
     */
    private function registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => self::LOG_CATEGORY,
            'categories' => [self::LOG_CATEGORY],
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
            Craft::info($message, self::LOG_CATEGORY);
        }
    }

    /**
     * Log an error message
     * @param mixed $message
     */
    public static function error($message): void
    {
        Craft::error($message, self::LOG_CATEGORY);
    }

    /**
     * Log a warning message
     * @param mixed $message
     */
    public static function warning($message): void
    {
        Craft::warning($message, self::LOG_CATEGORY);
    }
}