<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\AutoTextType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class AutoTextFieldType extends TextFieldType
{
    const TYPE = "auto_text";
    const FORM_TYPE = AutoTextType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['expression', 'auto_update', 'text_widget', 'not_empty', 'description'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['expression'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'expression' => $field->getSettings()->expression,
                'text_widget' => $field->getSettings()->text_widget ?? TextType::class,
                'auto_update' => !!$field->getSettings()->auto_update
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $schemaTypeManager->getSchemaType('AutoTextField');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $schemaTypeManager->getSchemaType('AutoTextFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        // Automatic value was generated and stored on submit.
        return [
            'auto' => $value['auto'] ?? true,
            'text' => $value['text'] ?? '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        // TODO
    }
}
