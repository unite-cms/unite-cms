<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 31.08.18
 * Time: 14:32
 */

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\StateType;
use UniteCMS\CoreBundle\Model\StateSettings;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class StateFieldType extends FieldType
{
    const TYPE = "state";
    const FORM_TYPE = StateType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['description', 'initial_place', 'places', 'transitions', 'form_group'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['initial_place', 'places', 'transitions'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
                [
                    'settings' => (array) $field->getSettings()
                ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return $field->getSettings()->initial_place ?? null;
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager) {
        return $schemaTypeManager->getSchemaType('StateFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        // return NULL on empty value
        if (empty($value))
        {
            return NULL;
        }
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        // initial place must be a string
        if(!is_string($settings->initial_place)) {
            $context->buildViolation('workflow_invalid_initial_place')->atPath('initial_place')->addViolation();
            return;
        }

        // places must be an array.
        if(!is_array($settings->places)) {
            $context->buildViolation('workflow_invalid_places')->atPath('places')->addViolation();
            return;
        }

        // transitions must be a array.
        if(!is_array($settings->transitions)) {
            $context->buildViolation('workflow_invalid_transitions')->atPath('transitions')->addViolation();
            return;
        }

        $state_settings = StateSettings::createFromArray((array) $settings);

        $context->getViolations()->addAll($context->getValidator()->validate($state_settings));

    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['settings'] = [
            'places' => $field ? $field->getSettings()->places : []
        ];
    }
}
