<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\BasicFieldTypeTrait;

class EmailFieldType extends FieldType
{
    use BasicFieldTypeTrait;

    const TYPE = "email";
    const FORM_TYPE = EmailType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['required'];
}