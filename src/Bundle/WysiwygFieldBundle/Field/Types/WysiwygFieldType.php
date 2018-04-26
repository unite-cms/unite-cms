<?php

namespace UniteCMS\WysiwygFieldBundle\Field\Types;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
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

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        // Validate allowed and required settings.
        $violations = parent::validateSettings($field, $settings);

        // Only continue, if there are no violations yet.
        if(!empty($violations)) {
            return $violations;
        }

        // Check allowed theme.
        if(!empty($settings->theme)) {
            if(!in_array($settings->theme, self::ALLOWED_THEMES)) {
                $violations[] = new ConstraintViolation(
                    'validation.unknown_theme',
                    'validation.unknown_theme',
                    [],
                    $settings,
                    'theme',
                    $settings
                );
            }
        }

        // Check allowed toolbar options.
        // TODO

        return $violations;
    }
}
