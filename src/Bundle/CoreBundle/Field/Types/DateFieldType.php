<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\DateFieldTypeTrait;

class DateFieldType extends FieldType
{
    use DateFieldTypeTrait;

    const TYPE = "date";
    const FORM_TYPE = DateType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['widget', 'required'];
}