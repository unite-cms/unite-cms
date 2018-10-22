<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 13:01
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Form\LinkType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class LinkFieldType extends FieldType
{
    const TYPE = "link";
    const FORM_TYPE = LinkType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['title_widget', 'target_widget', 'required', 'empty_data'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'title_widget' => $field->getSettings()->title_widget ?? false,
                'target_widget' => $field->getSettings()->target_widget ?? false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('LinkField');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('LinkFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value)
    {
        return (array) $value;
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {

        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        if (!empty($settings->title_widget) && !is_bool($settings->title_widget)) {
            $context->buildViolation('noboolean_value')->atPath('title_widget')->addViolation();
        }

        if (!empty($settings->target_widget) && !is_bool($settings->target_widget)) {
            $context->buildViolation('noboolean_value')->atPath('target_widget')->addViolation();
        }

    }

}