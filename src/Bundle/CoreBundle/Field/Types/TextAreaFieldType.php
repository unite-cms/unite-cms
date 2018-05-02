<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class TextAreaFieldType extends FieldType
{

    const TYPE = "textarea";

    const FORM_TYPE = TextareaType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['rows'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
              [
                'attr' => [
                    'rows' => $field->getSettings()->rows ?? 2
                ],
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

        // Check integer Values for rows
        if(!empty($settings->rows)) {
            if(!is_int($settings->rows)) {
                $violations[] = new ConstraintViolation(
                  'validation.nointeger_value',
                  'validation.nointeger_value',
                  [],
                  $settings,
                  'rows',
                  $settings
                );
            }
        }

        return $violations;
    }
}
