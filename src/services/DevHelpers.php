<?php

namespace astuteo\astuteosearchtransform\services;

use Craft;
use yii\base\Component;
use astuteo\astuteosearchtransform\services\TextExtraction;

/**
 * Dev Helpers service
 */
class DevHelpers extends Component
{
    public function getFieldPreview($field)
    {
        return (new TextExtraction)->extractTextFromMatrix($field);
    }
}
