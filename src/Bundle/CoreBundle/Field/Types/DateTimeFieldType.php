<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use UniteCMS\CoreBundle\Entity\FieldableField;

class DateTimeFieldType extends DateFieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;
    const SETTINGS = ['required', 'empty_data'];

    /**
     * {@inheritdoc}
     */
    function getViewFieldDefinition(FieldableField $field = null) : array {
        return [
            'label' => $field ? $field->getTitle() : null,
            'type' => 'date',
        ];
    }
}