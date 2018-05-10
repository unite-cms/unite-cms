<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\BasicFieldTypeTrait;

class IntegerFieldType extends FieldType
{
    use BasicFieldTypeTrait;

    const TYPE = "integer";
    const FORM_TYPE = IntegerType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['required'];
}