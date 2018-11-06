<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;

class DateFieldType extends FieldType
{
    const TYPE = "date";
    const FORM_TYPE = DateType::class;
    const SETTINGS = ['required', 'initial_data', 'description'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'widget' => 'single_text',
                'input' => 'string',
            ]
        );
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

        // validate if initial data is a valid date
        if (isset($settings->initial_data)) {

            $errors = $context->getValidator()->validate(
                $settings->initial_data,
                new Assert\Date()
            );

            if (count($errors) > 0) {
                $context->buildViolation('invalid_initial_data')->atPath('initial_data')->addViolation();
            }
        }
    }
}