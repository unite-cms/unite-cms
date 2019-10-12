<?php


namespace UniteCMS\CoreBundle\Field;


use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

interface FieldTypeInterface
{
    static function getType(): string;
    public function GraphQLInputType(ContentTypeField $field) : string;

    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field);
}
