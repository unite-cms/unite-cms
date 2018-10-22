<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use UniteCMS\CoreBundle\Field\FieldType;

class NumberFieldType extends FieldType
{
    const TYPE = "number";
    const FORM_TYPE = NumberType::class;
    const SETTINGS = ['required', 'empty_data'];
}