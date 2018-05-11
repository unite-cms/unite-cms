<?php

namespace UniteCMS\CoreBundle\Field;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\BasicFieldTypeTrait;

/**
 * a trait for reusing date field settings validation
 */
trait DateFieldTypeTrait
{
    use BasicFieldTypeTrait {
        BasicFieldTypeTrait::validateSettings as parentValidateSettings;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
              [
                  'widget' => $field->getSettings()->widget ?? 'single_text',
                  'required' => $field->getSettings()->required ?? false
              ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        $violations = $this->parentValidateSettings($field, $settings);

        // Only continue, if there are no violations yet.
        if(!empty($violations)) {
            return $violations;
        }

        $allowed_widgets = [
            'choice',
            'text',
            'single_text'
        ];

        // Check if widget setting is allowed
        if(isset($settings->widget) && !in_array($settings->widget, $allowed_widgets)) {
            $violations[] = new ConstraintViolation(
                'validation.wrong_widget_value',
                'validation.wrong_widget_value',
                [],
                $settings,
                'widget',
                $settings
            );
        }

        return $violations;
    }
}