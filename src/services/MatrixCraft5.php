<?php

namespace astuteo\astuteosearchtransform\services;

use craft\fields\PlainText;
use craft\ckeditor\Field as CKEditorField;

class MatrixCraft5
{
    public function fieldsFromMatrix($EntryQuery)
    {
        $blocks = $EntryQuery;

        foreach ($blocks as $block) {
            $fieldValues = [];
            foreach ($block->getFieldLayout()->getCustomFields() as $field) {
                if ($field instanceof PlainText) {
                    $fieldValues['plainTextFields'][$field->handle] = $block->getFieldValue($field->handle);
                } elseif ($field instanceof CKEditorField) {
                    $fieldValues['ckEditorFields'][$field->handle] = $block->getFieldValue($field->handle);
                }
            }
            // For debugging output
            print_r($fieldValues);
        }
        return 'yes';
    }

}