<?php

namespace App\Bundle\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use UniteCMS\CoreBundle\Field\FieldType;

class DateTimeFieldType extends FieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;
}