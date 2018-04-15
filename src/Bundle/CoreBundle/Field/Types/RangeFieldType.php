<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\RangeType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;

class RangeFieldType extends FieldType
{
    const TYPE = "range";
    const FORM_TYPE = RangeType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['min', 'max', 'step'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(parent::getFormOptions($field), [
            'attr' => [
                'min' => $field->getSettings()->min ?? 0,
                'max' => $field->getSettings()->max ?? 100,
                'step' => $field->getSettings()->step ?? 1
            ],
        ]);
    }
}
