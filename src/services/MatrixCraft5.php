<?php

namespace astuteo\astuteosearchtransform\services;

use astuteo\astuteosearchtransform\AstuteoSearchTransform;
use craft\elements\Entry;
use craft\errors\InvalidFieldException;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\fields\Entries;
use craft\ckeditor\Field as CKEditorField;

class MatrixCraft5
{

    /**
     * @throws InvalidFieldException
     */
    public function fieldsFromMatrixString($EntryQuery): string
    {
        $result = $this->fieldsFromMatrixArray($EntryQuery);
        return implode(' ', $result);
    }

    /**
     * @throws InvalidFieldException
     */
    public function fieldsFromMatrixArray($EntryQuery): array
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
            
            if (isset($fieldValues['tableFields'])) {
                foreach ($fieldValues['tableFields'] as $field) {
                    if (!empty($field['plainText'])) {
                        $plainTextValues[] = $field['plainText'];
                    }
                }
            }
            
            if (isset($fieldValues['entryFields'])) {
                foreach ($fieldValues['entryFields'] as $field) {
                    if (!empty($field['plainText'])) {
                        $plainTextValues[] = $field['plainText'];
                    }
                }
            }
        }
        return $plainTextValues;
    }

    /**
     * @throws InvalidFieldException
     */
    public function fieldsFromMatrixWithRaw($EntryQuery): array
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
                } elseif ($field instanceof Table) {
                    $fieldValues['tableFields'][$field->handle] = $this->processTableField($block, $field);
                } elseif ($field instanceof Entries) {
                    $fieldValues['entryFields'][$field->handle] = $this->processEntryField($block, $field);
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
     * @param Entry $block The entry block
     * @param PlainText $field The field definition
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processPlainTextField(Entry $block, PlainText $field): array
    {
        return [
            'raw' => $block->getFieldValue($field->handle),
            'plainText' => $block->getFieldValue($field->handle)
        ];
    }

    /**
     * Process a CKEditor field
     *
     * @param Entry $block The entry block
     * @param CKEditorField $field The field definition
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processCKEditorField(Entry $block, CKEditorField $field): array
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

    /**
     * Process a Table field
     *
     * @param Entry $block The entry block
     * @param Table $field The field definition
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processTableField(Entry $block, Table $field): array
    {
        $fieldValue = $block->getFieldValue($field->handle);
        if (empty($fieldValue)) {
            return [
                'raw' => '',
                'plainText' => ''
            ];
        }
        $columns = $field->columns;
        
        $tableValues = [];
        $plainTextValues = [];

        foreach ($fieldValue as $row) {
            // This avoids processing both generic column names (col1, col2) and custom handles
            foreach ($columns as $column) {
                $handle = $column['handle'];
                if (!empty($row[$handle]) && is_string($row[$handle])) {
                    $tableValues[] = $row[$handle];
                    $plainTextValues[] = $row[$handle];
                }
            }
        }

        return [
            'raw' => $tableValues,
            'plainText' => implode(' ', $plainTextValues)
        ];
    }

    /**
     * Process an Entries field
     *
     * @param Entry $block The entry block
     * @param Entries $field The field definition
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processEntryField(Entry $block, Entries $field): array
    {
        $relatedEntries = $block->getFieldValue($field->handle)->all();
        if (empty($relatedEntries)) {
            return [
                'raw' => [],
                'plainText' => ''
            ];
        }

        $entryValues = [];
        $plainTextValues = [];

        foreach ($relatedEntries as $relatedEntry) {
            // Process all fields in the related entry
            $entryContent = $this->processRelatedEntry($relatedEntry);
            
            if (!empty($entryContent)) {
                $entryValues[] = $entryContent;
                $plainTextValues[] = implode(' ', $entryContent);
            }
        }

        return [
            'raw' => $entryValues,
            'plainText' => implode(' ', $plainTextValues)
        ];
    }

    /**
     * Process a related entry recursively to extract all text content
     *
     * @param Entry $entry The related entry
     * @return array An array of text content from the entry
     * @throws InvalidFieldException
     */
    private function processRelatedEntry(Entry $entry): array
    {
        $textContent = [];

        foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
            if ($field instanceof PlainText) {
                $fieldValue = $entry->getFieldValue($field->handle);
                if (!empty($fieldValue)) {
                    $textContent[] = $fieldValue;
                }
            } elseif ($field instanceof CKEditorField) {
                $fieldValue = $entry->getFieldValue($field->handle);
                if (!empty($fieldValue)) {
                    $textContent[] = (new TextExtraction)->cleanText((string)$fieldValue);
                }
            } elseif ($field instanceof Table) {
                $processedField = $this->processTableField($entry, $field);
                if (!empty($processedField['plainText'])) {
                    $textContent[] = $processedField['plainText'];
                }
            } elseif ($field instanceof Entries) {
                // Recursively process nested entries
                $nestedEntries = $entry->getFieldValue($field->handle)->all();
                foreach ($nestedEntries as $nestedEntry) {
                    $nestedContent = $this->processRelatedEntry($nestedEntry);
                    if (!empty($nestedContent)) {
                        $textContent = array_merge($textContent, $nestedContent);
                    }
                }
            }
        }

        return $textContent;
    }
}