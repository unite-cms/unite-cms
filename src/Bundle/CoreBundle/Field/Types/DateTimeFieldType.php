<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class DateTimeFieldType extends DateFieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;
}