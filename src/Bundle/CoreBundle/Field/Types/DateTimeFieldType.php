<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class DateTimeFieldType extends DateFieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;
    const SETTINGS = ['required', 'initial_data', 'description'];

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['type'] = 'date';
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

        // validate if initial data is a valid date time
        if (isset($settings->initial_data)) {

            $errors = $context->getValidator()->validate(
                $settings->initial_data,
                new Assert\DateTime()
            );

            if (count($errors) > 0) {
                $context->buildViolation('invalid_initial_data')->atPath('initial_data')->addViolation();
            }
        }
    }
}