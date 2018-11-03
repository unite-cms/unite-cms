<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use UniteCMS\CoreBundle\Field\FieldType;

class IntegerFieldType extends FieldType
{
    const TYPE = "integer";
    const FORM_TYPE = IntegerType::class;
    const SETTINGS = ['required', 'initial_data', 'description'];
}