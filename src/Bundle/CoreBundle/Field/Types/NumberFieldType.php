<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\BasicFieldTypeTrait;

class NumberFieldType extends FieldType
{
    use BasicFieldTypeTrait;

    const TYPE = "number";
    const FORM_TYPE = NumberType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['required'];
}