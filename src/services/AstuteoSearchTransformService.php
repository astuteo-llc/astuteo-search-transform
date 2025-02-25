<?php
declare(strict_types=1);

namespace astuteo\astuteosearchtransform\services;

use Craft;
use yii\base\BaseObject;
use astuteo\astuteosearchtransform\AstuteoSearchTransform;

/**
 * @deprecated in 3.0.0. Use TextExtraction instead.
 */
class AstuteoSearchTransformService extends TextExtraction
{
    /**
     * Constructor.
     * 
     * @param array $config
     */
    public function __construct($config = [])
    {
        AstuteoSearchTransform::info('AstuteoSearchTransformService is being used directly. Use AstuteoSearchTransform::getInstance()->textExtraction instead.');
        parent::__construct($config);
    }
}
