<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class EmailFieldType extends FieldType
{
    const TYPE = "email";
    const FORM_TYPE = EmailType::class;
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

        // validate if initial data is a valid email
        if (isset($settings->initial_data) && !filter_var($settings->initial_data, FILTER_VALIDATE_EMAIL)) {
            $context->buildViolation('invalid_initial_data')->atPath('initial_data')->addViolation();
        }
    }
}