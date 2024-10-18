<?php
declare(strict_types=1);

namespace astuteo\astuteosearchtransform\services;

use Craft;
use yii\base\BaseObject;

/**
 * @deprecated in 3.0.0. Use TextExtractionService instead.
 */
class AstuteoSearchTransformService extends TextExtraction
{
    public function __construct($config = [])
    {
        Craft::$app->getDeprecator()->log('AstuteoSearchTransformService', 'AstuteoSearchTransformService has been deprecated. Use TextExtraction instead.');
        parent::__construct($config);
    }
}
