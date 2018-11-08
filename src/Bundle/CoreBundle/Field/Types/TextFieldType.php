<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use UniteCMS\CoreBundle\Field\FieldType;

class TextFieldType extends FieldType
{
    const TYPE = "text";
    const FORM_TYPE = TextType::class;
}
