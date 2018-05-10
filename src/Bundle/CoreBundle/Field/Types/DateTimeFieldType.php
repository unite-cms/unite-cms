<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\DateFieldTypeTrait;

class DateTimeFieldType extends FieldType
{
    use DateFieldTypeTrait;

    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['widget', 'required'];
}