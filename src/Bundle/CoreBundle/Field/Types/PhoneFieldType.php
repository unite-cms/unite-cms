<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use UniteCMS\CoreBundle\Field\FieldType;

class PhoneFieldType extends FieldType
{
    const TYPE = "phone";
    const FORM_TYPE = TelType::class;
    const SETTINGS = ['required', 'empty_data'];
}