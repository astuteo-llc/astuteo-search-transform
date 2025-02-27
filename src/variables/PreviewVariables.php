<?php

namespace astuteo\astuteosearchtransform\variables;

use astuteo\astuteosearchtransform\services\DevHelpers;
class PreviewVariables
{
    public function getField($field)
    {
       return (new DevHelpers)->getFieldPreview($field);
    }
}