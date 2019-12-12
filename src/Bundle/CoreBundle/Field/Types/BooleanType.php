<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class BooleanType extends AbstractFieldType
{
    const TYPE = 'boolean';
    const GRAPHQL_INPUT_TYPE = Type::BOOLEAN;

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        $value = (is_bool($fieldData->getData()))? $fieldData->getData(): null;
        return $fieldData->resolveData('', ($field->isNonNull() || $field->isListOf()) ? false : $value);
    }
}
