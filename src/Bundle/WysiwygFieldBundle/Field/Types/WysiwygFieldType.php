<?php

namespace UniteCMS\WysiwygFieldBundle\Field\Types;

use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\WysiwygFieldBundle\Form\WysiwygType;

class WysiwygFieldType extends FieldType
{
    const TYPE                      = "wysiwyg";
    const FORM_TYPE                 = WysiwygType::class;
    const SETTINGS                  = ['toolbar', 'theme', 'placeholder'];
    const REQUIRED_SETTINGS         = ['toolbar'];

    const ALLOWED_THEMES            = ['snow', 'bubble'];
    const ALLOWED_TOOLBAR_OPTIONS   = [
        'bold', 'italic', 'underline', 'strike',
        'blockquote', 'clean', 'link',
        ['header' => 1], ['header' => 2], ['header' => 3], ['header' => 4], ['header' => 5], ['header' => 6],
        ['list' => 'ordered'], ['list' => 'bullet'], ['list' => 'checked'],
        ['indent' => '-1'], ['indent' => '+1'],
        ['script' => 'sub'], ['script' => 'super'],
        ['direction' => 'rtl'],
    ];


    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $theme = $field->getSettings()->theme ?? 'snow';
        $placeholder = $field->getSettings()->placeholder ?? '';

        return array_merge(
            parent::getFormOptions($field),
            [
                'attr' => [
                    'data-options' => json_encode([
                        'theme' => $theme,
                        'placeholder' => $placeholder,
                        'modules' => [
                            'toolbar' => $field->getSettings()->toolbar,
                        ],
                    ]),
                ],
            ]
        );
    }
}
