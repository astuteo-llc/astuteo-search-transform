<?php
/**
 * Astuteo Search Transform plugin for Craft CMS 3.x
 *
 * Helper to modify text on projects Astuteo is using Algolia search on.
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2020 Astuteo
 */

namespace astuteo\astuteosearchtransform;

use astuteo\astuteosearchtransform\services\AstuteoSearchTransformService as AstuteoSearchTransformServiceService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Astuteo
 * @package   AstuteoSearchTransform
 * @since     1.0.0
 *
 * @property  AstuteoSearchTransformServiceService $astuteoSearchTransformService
 */
class AstuteoSearchTransform extends Plugin
{
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();
        self::$plugin = $this;
    }

    // Protected Methods
    // =========================================================================
}
