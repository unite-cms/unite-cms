<?php

namespace UniteCMS\MediaBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\Types\AbstractFieldType;

class MediaFileType extends AbstractFieldType
{
    const TYPE = 'mediaFile';
    const GRAPHQL_INPUT_TYPE = 'UniteMediaFileInput';

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UniteMediaFile'];
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/' . static::getType() . '.graphql');
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        return [
            'todo' => $fieldData->resolveData('todo', 'foo'),
        ];
    }
}
