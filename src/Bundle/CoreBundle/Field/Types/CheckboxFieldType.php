<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use UnitedCMS\CoreBundle\Field\FieldType;

class CheckboxFieldType extends FieldType
{
    const TYPE = "checkbox";
    const FORM_TYPE = CheckboxType::class;
}