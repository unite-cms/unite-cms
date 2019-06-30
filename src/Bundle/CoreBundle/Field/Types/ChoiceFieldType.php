<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class ChoiceFieldType extends FieldType
{
    const TYPE = "choice";
    const FORM_TYPE = ChoiceType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['not_empty', 'description', 'default', 'choices', 'form_group'];

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
    }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        parent::validateDefaultValue($value, $settings, $context);
        if($context->getViolations()->count() == 0) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($value, new Assert\Choice(['choices' => $settings->choices]))
            );
        }
    }
}
