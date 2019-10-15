<?php


namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

abstract class AbstractFieldType  implements FieldTypeInterface, SchemaProviderInterface
{
    const TYPE = null;
    const GRAPHQL_INPUT_TYPE = Type::STRING;

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/' . static::getType() . '.graphql');
    }

    /**
     * {@inheritDoc}
     */
    static function getType(): string {
        return static::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return static::GRAPHQL_INPUT_TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {

        if($fieldData instanceof FieldDataList) {
            $resolve = [];
            foreach($fieldData->rows() as $rowData) {
                $resolve[] = $this->resolveRowData($content, $field, $rowData);
            }
            return $resolve;
        }

        return $this->resolveRowData($content, $field, $fieldData);
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeData(ContentTypeField $field, $inputData = null): FieldData {

        if($field->isListOf()) {
            $inputData = $inputData ? (is_array($inputData) ? $inputData : [$inputData]) : [];
            foreach($inputData as $key => $inputRowData) {
                $inputData[$key] = $this->normalizeRowData($field, $inputRowData);
            }
            return new FieldDataList($inputData);
        }

        return $this->normalizeRowData($field, $inputData);
    }

    /**
     * @param \UniteCMS\CoreBundle\Content\ContentInterface $content
     * @param \UniteCMS\CoreBundle\ContentType\ContentTypeField $field
     * @param \UniteCMS\CoreBundle\Content\FieldData $fieldData
     *
     * @return mixed
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return (string)$fieldData;
    }

    /**
     * @param \UniteCMS\CoreBundle\ContentType\ContentTypeField $field
     * @param null $inputData
     *
     * @return \UniteCMS\CoreBundle\Content\FieldData
     */
    protected function normalizeRowData(ContentTypeField $field, $inputData = null) : FieldData {
        return new FieldData($inputData);
    }
}
