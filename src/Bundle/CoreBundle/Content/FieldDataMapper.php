<?php


namespace UniteCMS\CoreBundle\Content;

use InvalidArgumentException;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class FieldDataMapper
{
    protected $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Map input data to a content object, asking all field types how to do it.
     *
     * @param Domain $domain
     * @param ContentInterface $content
     * @param $inputData
     * @param ContentType|null $contentType
     *
     * @return array
     */
    public function mapToFieldData(Domain $domain, ContentInterface $content, $inputData, ContentType $contentType = null) : array {

        $inputData = empty($inputData) || !is_array($inputData) ? [] : $inputData;
        $normalizedData = [];

        if(empty($contentType)) {
            $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());;
        }

        if(!$contentType) {
            throw new InvalidArgumentException(sprintf('Content Type "%s" was not found.', $content->getType()));
        }

        // Ask all defined fields on this content type for field data based on inputData.
        foreach($contentType->getFields() as $id => $field) {

            $fieldData = $inputData[$id] ?? null;

            if($field->isListOf()) {

                $listData = [];
                foreach(($fieldData ?? []) as $rowId => $rowData) {
                    $listData[$rowId] = $this->normalizeFieldData($field, $domain, $content, $rowData);
                }
                $normalizedData[$id] = new FieldDataList($listData);
            }

            else {
                $normalizedData[$id] = $this->normalizeFieldData($field, $domain, $content, $fieldData);
            }
        }

        return $normalizedData;
    }

    /**
     * @param ContentTypeField $field
     * @param Domain $domain
     * @param ContentInterface $content
     * @param $rowData
     *
     * @return FieldData|null
     */
    protected function normalizeFieldData(ContentTypeField $field, Domain $domain, ContentInterface $content, $rowData) {
        if(!empty($field->getUnionTypes())) {

            if(empty($rowData)) {
                return null;
            }

            $unionType = $domain->getContentTypeManager()->getUnionContentType($field->getReturnType());
            $selectedUnionType = array_keys($rowData)[0];
            $rowData = $rowData[$selectedUnionType];
            $field = $unionType->getField($selectedUnionType);
        }

        $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
        return $fieldType->normalizeInputData($content, $field, $rowData);
    }
}

