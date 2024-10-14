<?php
declare(strict_types=1);

/**
 * Astuteo Search Transform plugin for Craft CMS 3.x
 *
 * Helper to modify text on projects Astuteo is using Algolia search on.
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 * @package   AstuteoSearchTransform
 */
namespace astuteo\astuteosearchtransform;

use astuteo\astuteosearchtransform\services\AstuteoSearchTransformService as AstuteoSearchTransformServiceService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

class AstuteoSearchTransform extends Plugin
{
    /**
     * @var AstuteoSearchTransform
     */
    public static $plugin;

    /**
     * @var string
     */
    public string $schemaVersion = '5.1.0';

    /**
     * Initializes the plugin.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
    }
}
