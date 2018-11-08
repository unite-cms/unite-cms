<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
    const SETTINGS = ['not_empty', 'description', 'default', 'choices'];

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
}
