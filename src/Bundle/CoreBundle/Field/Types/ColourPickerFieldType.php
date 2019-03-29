<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\ColourPickerType;

class ColourPickerFieldType extends FieldType
{
    const TYPE = "colour_picker";
    const FORM_TYPE = ColourPickerType::class;
    const SETTINGS = ['description'];
}
