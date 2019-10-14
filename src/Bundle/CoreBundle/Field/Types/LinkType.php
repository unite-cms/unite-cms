<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

class LinkType implements FieldTypeInterface, SchemaProviderInterface
{
    const TYPE = 'link';

    /**
     * {@inheritDoc}
     */
    static function getType(): string {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return 'UniteLinkInput';
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field) {

        if(!$data = $content->getFieldData($fieldName)) {
            return null;
        }
        return [
            'url' => $data->resolveData('url', ''),
            'title' => $data->resolveData('title', ''),
            'target' => $data->resolveData('target', ''),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeData(ContentTypeField $field, $fieldData = null): FieldData {
        return new FieldData($fieldData);
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/link.graphql');
    }
}
