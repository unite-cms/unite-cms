<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

class LinkType extends AbstractFieldType implements SchemaProviderInterface
{
    const TYPE = 'link';
    const GRAPHQL_INPUT_TYPE = 'UniteLinkInput';

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/link.graphql');
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
