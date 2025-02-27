<?php

namespace astuteo\astuteosearchtransform\services;

use astuteo\astuteosearchtransform\AstuteoSearchTransform;
use craft\errors\InvalidFieldException;
use craft\fields\PlainText;
use craft\ckeditor\Field as CKEditorField;

class MatrixCraft5
{

    public function fieldsFromMatrixString($EntryQuery) {
        $result = $this->fieldsFromMatrixArray($EntryQuery);
        return implode(' ', $result);
    }
 
    public function fieldsFromMatrixArray($EntryQuery)
    {
        $result = $this->fieldsFromMatrixWithRaw($EntryQuery);
        $plainTextValues = [];
        
        foreach ($result as $fieldValues) {
            if (isset($fieldValues['plainTextFields'])) {
                foreach ($fieldValues['plainTextFields'] as $field) {
                    if (!empty($field['plainText'])) {
                        $plainTextValues[] = $field['plainText'];
                    }
                }
            }
            
            if (isset($fieldValues['ckEditorFields'])) {
                foreach ($fieldValues['ckEditorFields'] as $field) {
                    if (!empty($field['plainText'])) {
                        $plainTextValues[] = $field['plainText'];
                    }
                }
            }
        }
        return $plainTextValues;
    }
    
    public function fieldsFromMatrixWithRaw($EntryQuery)
    {
        $blocks = $EntryQuery;
        $result = [];

        foreach ($blocks as $block) {
            $fieldValues = [];
            foreach ($block->getFieldLayout()->getCustomFields() as $field) {
                if ($field instanceof PlainText) {
                    $fieldValues['plainTextFields'][$field->handle] = $this->processPlainTextField($block, $field);
                } elseif ($field instanceof CKEditorField) {
                    $fieldValues['ckEditorFields'][$field->handle] = $this->processCKEditorField($block, $field);
                } else {
                    AstuteoSearchTransform::info('Unsupported field type: ' . get_class($field));
                }

            }
            $result[] = $fieldValues;
        }
        return $result;
    }

    /**
     * Process a PlainText field
     * 
     * @param \craft\elements\Entry $block The entry block
     * @param PlainText $field The field definition
     * @return array An array with raw and plainText versions of the content
     */
    private function processPlainTextField($block, $field): array
    {
        return [
            'raw' => $block->getFieldValue($field->handle),
            'plainText' => $block->getFieldValue($field->handle)
        ];
    }

    /**
     * Process a CKEditor field
     *
     * @param \craft\elements\Entry $block The entry block
     * @param CKEditorField $field The field definition
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processCKEditorField($block, $field): array
    {
        $fieldValue = $block->getFieldValue($field->handle);
        if (!$fieldValue) {
            return [
                'raw' => '',
                'plainText' => ''
            ];
        }

        $htmlContent = (string)$fieldValue;
        $plainText = (new TextExtraction)->cleanText($htmlContent);

        return [
            'raw' => $htmlContent,
            'plainText' => $plainText
        ];
    }
}