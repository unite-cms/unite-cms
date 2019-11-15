<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class LinkType extends AbstractFieldType
{
    const TYPE = 'link';
    const GRAPHQL_INPUT_TYPE = 'UniteLinkInput';

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UniteLink'];
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return [
            'url' => $fieldData->resolveData('url', ''),
            'title' => $fieldData->resolveData('title', ''),
            'target' => $fieldData->resolveData('target', ''),
        ];
    }
}
