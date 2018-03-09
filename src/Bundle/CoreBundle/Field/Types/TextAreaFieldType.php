<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use UnitedCMS\CoreBundle\Field\FieldType;

class TextAreaFieldType extends FieldType
{
    const TYPE = "textarea";
    const FORM_TYPE = TextareaType::class;
}