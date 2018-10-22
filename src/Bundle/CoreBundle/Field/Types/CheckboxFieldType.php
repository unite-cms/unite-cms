<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use UniteCMS\CoreBundle\Field\FieldType;

class CheckboxFieldType extends FieldType
{
    const TYPE = "checkbox";
    const FORM_TYPE = CheckboxType::class;
    const SETTINGS = ['required', 'empty_data'];
}
