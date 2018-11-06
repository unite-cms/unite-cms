<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class IntegerFieldType extends FieldType
{
    const TYPE = "integer";
    const FORM_TYPE = IntegerType::class;
    const SETTINGS = ['required', 'initial_data', 'description'];

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

        // validate if initial data is a integer
        if (isset($settings->initial_data) && !is_int($settings->initial_data)) {
            $context->buildViolation('invalid_initial_data')->atPath('initial_data')->addViolation();
        }
    }

}