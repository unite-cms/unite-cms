<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use GraphQL\Type\Definition\Type;

class TextType implements FieldTypeInterface
{
    const TYPE = 'text';

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
        return Type::STRING;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field) {
        return (string)$content->getFieldData($fieldName);
    }
}
