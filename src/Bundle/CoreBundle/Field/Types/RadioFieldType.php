<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\RadioType;
use UniteCMS\CoreBundle\Field\FieldType;

class RadioFieldType extends FieldType
{
    const TYPE = "radio";
    const FORM_TYPE = RadioType::class;
}