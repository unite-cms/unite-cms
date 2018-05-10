<?php

namespace UniteCMS\CoreBundle\Field;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

/**
 * a trait for reusing date field settings validation
 */
trait BasicFieldTypeTrait 
{
    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'required' => $field->getSettings()->required ?? false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        // Validate allowed and required settings.
        $violations = parent::validateSettings($field, $settings);

        // Only continue, if there are no violations yet.
        if(!empty($violations)) {
            return $violations;
        }

        if (isset($settings->required) && !is_bool($settings->required)) {
            $violations[] = new ConstraintViolation(
                'validation.no_boolean_value',
                'validation.no_boolean_value',
                [],
                $settings,
                'required',
                $settings
            );
        }

        return $violations;
    }
}