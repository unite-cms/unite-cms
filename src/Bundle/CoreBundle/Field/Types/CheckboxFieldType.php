<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class CheckboxFieldType extends FieldType
{
    const TYPE = "checkbox";
    const FORM_TYPE = CheckboxType::class;
    const SETTINGS = ['default', 'description', 'hidden'];

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'boolean', 'message' => 'invalid_initial_data']))
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::boolean();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::boolean();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        return (boolean)$value;
    }

    /**
     * Basic settings validation based on self::SETTINGS and self::REQUIRED_SETTINGS constants. More sophisticated
     * validation should be done in child implementations.
     *
     * @param FieldableFieldSettings $settings
     * @param ExecutionContextInterface $context
     *
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        $settingsArray = $settings ? get_object_vars($settings) : [];

        // validate hidden is boolean
        if (!empty($settingsArray['hidden'])) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settingsArray['hidden'], new Assert\Type(['type' => 'boolean', 'message' => 'noboolean_value']))
            );
        }
    }
}
