<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class ChoiceFieldType extends FieldType
{
    const TYPE = "choice";
    const FORM_TYPE = ChoiceType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['choices', 'required', 'empty_data'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['choices'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'choices' => $field->getSettings()->choices,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['type'] = 'choice';
        $settings['settings'] = [
            'choices' => $field ? array_flip($field->getSettings()->choices) : []
        ];
        $settings['assets'] = [
            ['js' => 'main.js', 'package' => 'UniteCMSWysiwygFieldBundle'],
            ['css' => 'main.css', 'package' => 'UniteCMSWysiwygFieldBundle'],
        ];
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

        // validate if empty data is inside choice values
        if (isset($settings->empty_data) && !in_array($settings->empty_data, $settings->choices)) {
            $context->buildViolation('emptydata_not_inside_values')->atPath('choices')->addViolation();
        }

    }
}
