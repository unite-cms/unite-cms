<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Form\AutoTextType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class AutoTextFieldType extends TextFieldType
{
    const TYPE = "auto_text";
    const FORM_TYPE = AutoTextType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['expression', 'not_empty', 'description', 'default'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['expression'];


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
        if(!is_array($value) || !array_key_exists('auto', $value) || !array_key_exists('text', $value)) {
            return null;
        }

        // If auto is set to true, we need to resolve the content of this field here.
        if(!!$value['auto']) {
            $value['text'] = 'TODO: Set auto text';
        }

        return $value;
    }
}
