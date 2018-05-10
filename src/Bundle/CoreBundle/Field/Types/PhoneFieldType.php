<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\BasicFieldTypeTrait;

class PhoneFieldType extends FieldType
{
    use BasicFieldTypeTrait;

    const TYPE = "phone";
    const FORM_TYPE = TelType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['required'];
}