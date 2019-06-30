<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class RangeFieldType extends FieldType
{
    const TYPE = "range";
    const FORM_TYPE = RangeType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['not_empty', 'description', 'default', 'min', 'max', 'step', 'form_group'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'attr' => [
                    'min' => $field->getSettings()->min ?? 0,
                    'max' => $field->getSettings()->max ?? 100,
                    'step' => $field->getSettings()->step ?? 1,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'integer', 'message' => 'invalid_initial_data']))
        );
    }
}
