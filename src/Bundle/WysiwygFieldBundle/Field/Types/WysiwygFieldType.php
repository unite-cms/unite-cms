<?php

namespace UniteCMS\WysiwygFieldBundle\Field\Types;

use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\WysiwygFieldBundle\Form\WysiwygType;

class WysiwygFieldType extends FieldType
{
    const TYPE                      = "wysiwyg";
    const FORM_TYPE                 = WysiwygType::class;
    const SETTINGS                  = ['toolbar'];
    const REQUIRED_SETTINGS         = ['toolbar'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
            ]
        );
    }
}
