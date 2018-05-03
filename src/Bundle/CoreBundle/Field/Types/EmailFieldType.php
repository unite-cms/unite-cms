<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use UniteCMS\CoreBundle\Field\FieldType;

class EmailFieldType extends FieldType
{
    const TYPE = "email";
    const FORM_TYPE = EmailType::class;
}