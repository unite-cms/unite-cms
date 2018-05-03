<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use UniteCMS\CoreBundle\Field\FieldType;

class DateFieldType extends FieldType
{
    const TYPE = "date";
    const FORM_TYPE = DateType::class;
}