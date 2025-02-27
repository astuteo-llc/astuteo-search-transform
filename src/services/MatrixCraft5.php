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
     * Extract all text content from matrix blocks and return as a single string
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $excludeHandles Optional array of field handles to exclude
     * @return string Text content from all matrix blocks concatenated with spaces
     * @throws InvalidFieldException
     */
    public function extractTextFromMatrixBlocks($matrixBlocks, array $excludeHandles = []): string
    {
        $contentArray = $this->extractTextArrayFromMatrixBlocks($matrixBlocks, $excludeHandles);
        return implode(' ', $contentArray);
    }

    /**
     * Extract text content from only the specified field handles in matrix blocks
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $includeHandles Array of field handles to include (all others will be excluded)
     * @return string Text content from included fields concatenated with spaces
     * @throws InvalidFieldException
     */
    public function extractTextFromMatrixBlocksInclude($matrixBlocks, array $includeHandles): string
    {
        $contentArray = $this->extractTextArrayFromMatrixBlocksInclude($matrixBlocks, $includeHandles);
        return implode(' ', $contentArray);
    }

    /**
     * Extract all text content from matrix blocks and return as an array of strings
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $excludeHandles Optional array of field handles to exclude
     * @return array Array of text content extracted from matrix blocks
     * @throws InvalidFieldException
     */
    public function extractTextArrayFromMatrixBlocks($matrixBlocks, array $excludeHandles = []): array
    {
        $result = $this->extractStructuredContentFromMatrixBlocks($matrixBlocks, $excludeHandles);
        $plainTextValues = [];

        foreach ($result as $fieldValues) {
            $this->collectPlainTextFromFields($fieldValues, 'plainTextFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'ckEditorFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'tableFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'entryFields', $plainTextValues);
        }

        return $plainTextValues;
    }

    /**
     * Extract text content from only the specified field handles in matrix blocks
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $includeHandles Array of field handles to include (all others will be excluded)
     * @return array Array of text content extracted from matrix blocks
     * @throws InvalidFieldException
     */
    public function extractTextArrayFromMatrixBlocksInclude($matrixBlocks, array $includeHandles): array
    {
        $result = $this->extractStructuredContentFromMatrixBlocksInclude($matrixBlocks, $includeHandles);
        $plainTextValues = [];

        foreach ($result as $fieldValues) {
            $this->collectPlainTextFromFields($fieldValues, 'plainTextFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'ckEditorFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'tableFields', $plainTextValues);
            $this->collectPlainTextFromFields($fieldValues, 'entryFields', $plainTextValues);
        }

        return $plainTextValues;
    }

    /**
     * Helper method to collect plain text values from different field types
     *
     * @param array $fieldValues The field values array
     * @param string $fieldType The field type key to check
     * @param array &$plainTextValues Reference to array where values will be collected
     */
    private function collectPlainTextFromFields(array $fieldValues, string $fieldType, array &$plainTextValues): void
    {
        if (isset($fieldValues[$fieldType])) {
            foreach ($fieldValues[$fieldType] as $field) {
                if (!empty($field['plainText'])) {
                    $plainTextValues[] = $field['plainText'];
                }
            }
        }
    }

    /**
     * Extract detailed structured content from matrix blocks with raw and plainText versions
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $excludeHandles Optional array of field handles to exclude
     * @return array Structured array of content from matrix blocks
     * @throws InvalidFieldException
     */
    public function extractStructuredContentFromMatrixBlocks($matrixBlocks, array $excludeHandles = []): array
    {
        $blocks = $matrixBlocks;
        $result = [];

        foreach ($blocks as $block) {
            $fieldValues = [];
            foreach ($block->getFieldLayout()->getCustomFields() as $field) {
                // Skip excluded fields
                if (in_array($field->handle, $excludeHandles, true)) {
                    continue;
                }

                if ($field instanceof PlainText) {
                    $fieldValues['plainTextFields'][$field->handle] = $this->processPlainTextField($block, $field);
                } elseif ($field instanceof CKEditorField) {
                    $fieldValues['ckEditorFields'][$field->handle] = $this->processCKEditorField($block, $field);
                } elseif ($field instanceof Table) {
                    $fieldValues['tableFields'][$field->handle] = $this->processTableField($block, $field);
                } elseif ($field instanceof Entries) {
                    $fieldValues['entryFields'][$field->handle] = $this->processEntryField($block, $field, $excludeHandles);
                } else {
                    AstuteoSearchTransform::info('Unsupported field type: ' . get_class($field));
                }
            }
            $result[] = $fieldValues;
        }
        return $result;
    }

    /**
     * Extract detailed structured content from matrix blocks for only the specified field handles
     *
     * @param array|iterable $matrixBlocks The matrix blocks to process
     * @param array $includeHandles Array of field handles to include (all others will be excluded)
     * @return array Structured array of content from matrix blocks
     * @throws InvalidFieldException
     */
    public function extractStructuredContentFromMatrixBlocksInclude($matrixBlocks, array $includeHandles): array
    {
        $blocks = $matrixBlocks;
        $result = [];

        foreach ($blocks as $block) {
            $fieldValues = [];
            foreach ($block->getFieldLayout()->getCustomFields() as $field) {
                // Skip fields not explicitly included
                if (!in_array($field->handle, $includeHandles, true)) {
                    continue;
                }

                if ($field instanceof PlainText) {
                    $fieldValues['plainTextFields'][$field->handle] = $this->processPlainTextField($block, $field);
                } elseif ($field instanceof CKEditorField) {
                    $fieldValues['ckEditorFields'][$field->handle] = $this->processCKEditorField($block, $field);
                } elseif ($field instanceof Table) {
                    $fieldValues['tableFields'][$field->handle] = $this->processTableField($block, $field);
                } elseif ($field instanceof Entries) {
                    $fieldValues['entryFields'][$field->handle] = $this->processEntryFieldInclude($block, $field, $includeHandles);
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
        $value = $block->getFieldValue($field->handle);
        return [
            'raw' => $value,
            'plainText' => $value
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
     * @param array $excludeHandles Optional array of field handles to exclude
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processEntryField(Entry $block, Entries $field, array $excludeHandles = []): array
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
            $entryContent = $this->processRelatedEntry($relatedEntry, $excludeHandles);

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
     * Process an Entries field with only the specified included fields
     *
     * @param Entry $block The entry block
     * @param Entries $field The field definition
     * @param array $includeHandles Array of field handles to include
     * @return array An array with raw and plainText versions of the content
     * @throws InvalidFieldException
     */
    private function processEntryFieldInclude(Entry $block, Entries $field, array $includeHandles): array
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
            // Process only included fields in the related entry
            $entryContent = $this->processRelatedEntryInclude($relatedEntry, $includeHandles);

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
     * @param array $excludeHandles Optional array of field handles to exclude
     * @return array An array of text content from the entry
     * @throws InvalidFieldException
     */
    private function processRelatedEntry(Entry $entry, array $excludeHandles = []): array
    {
        $textContent = [];

        foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
            // Skip excluded fields
            if (in_array($field->handle, $excludeHandles, true)) {
                continue;
            }

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
                    $nestedContent = $this->processRelatedEntry($nestedEntry, $excludeHandles);
                    if (!empty($nestedContent)) {
                        $textContent = array_merge($textContent, $nestedContent);
                    }
                }
            }
        }

        return $textContent;
    }

    /**
     * Process a related entry recursively to extract only the specified fields
     *
     * @param Entry $entry The related entry
     * @param array $includeHandles Array of field handles to include
     * @return array An array of text content from the entry
     * @throws InvalidFieldException
     */
    private function processRelatedEntryInclude(Entry $entry, array $includeHandles): array
    {
        $textContent = [];

        foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
            // Skip fields not explicitly included
            if (!in_array($field->handle, $includeHandles, true)) {
                continue;
            }

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
                    $nestedContent = $this->processRelatedEntryInclude($nestedEntry, $includeHandles);
                    if (!empty($nestedContent)) {
                        $textContent = array_merge($textContent, $nestedContent);
                    }
                }
            }
        }

        return $textContent;
    }
}