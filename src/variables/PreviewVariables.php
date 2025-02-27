<?php

namespace astuteo\astuteosearchtransform\variables;

use astuteo\astuteosearchtransform\services\DevHelpers;
class PreviewVariables
{
    public function getField($field, $excludeHandles = [])
    {
       return (new DevHelpers)->getFieldPreview($field,$excludeHandles);
    }

    public function getOnlyField($field, $include = [])
    {
        return (new DevHelpers)->getFieldOnlyPreview($field, $include);
    }
}