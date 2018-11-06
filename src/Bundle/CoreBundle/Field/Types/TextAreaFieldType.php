<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
    const SETTINGS = ['rows', 'required', 'initial_data', 'description'];

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
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        // Check integer Values for rows
        if(!empty($settings->rows)) {
            if(!is_int($settings->rows)) {
                $context->buildViolation('nointeger_value')->atPath('rows')->addViolation();
            }
        }

        // validate if initial data is a string
        if (isset($settings->initial_data) && !is_string($settings->initial_data)) {
            $context->buildViolation('invalid_initial_data')->atPath('initial_data')->addViolation();
        }
    }
}
