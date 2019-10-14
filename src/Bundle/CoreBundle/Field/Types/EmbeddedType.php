<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\Dummy\DummyContent;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;

class EmbeddedType implements FieldTypeInterface
{
    const TYPE = 'embedded';

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
        return sprintf('%sInput', $field->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field) {
        // TODO: Implement
        //return $content->getFieldData($fieldName);
        return null;

        /*if($field->isListOf()) {
            return [
                new DummyContent($field->getReturnType()),
                new DummyContent($field->getReturnType()),
            ];
        }
        return new DummyContent($field->getReturnType());*/
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeData(ContentTypeField $field, $fieldData = null): FieldData {
        return new FieldData($fieldData);
    }
}
