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
     * Validates a toolbar option and returns a violation if it is not allowed.
     * @param string $option
     * @param $settings
     * @return ConstraintViolation[]
     */
    protected function validateToolbarOption($option, $settings) : array {
        $violations = [];

        if(!in_array($option, self::ALLOWED_TOOLBAR_OPTIONS)) {
            $violations[] = new ConstraintViolation(
                'wysiwygfield.unknown_toolbar_option',
                'wysiwygfield.unknown_toolbar_option',
                [],
                $settings,
                'toolbar',
                $option
            );
        }
        return $violations;
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
                    'wysiwygfield.unknown_theme',
                    'wysiwygfield.unknown_theme',
                    [],
                    $settings,
                    'theme',
                    $settings->theme
                );
            }
        }

        // Check available toolbar options.
        if(empty($settings->toolbar)) {
            return [new ConstraintViolation(
                'not_blank',
                'not_blank',
                [],
                $settings,
                'toolbar',
                $settings
            )];
        }

        if(!is_array($settings->toolbar)) {
            return [new ConstraintViolation(
                'wysiwygfield.invalid_toolbar_definition',
                'wysiwygfield.invalid_toolbar_definition',
                [],
                $settings,
                'toolbar',
                $settings
            )];
        }

        // Validate toolbar options
        foreach($settings->toolbar as $option) {

            // case 1: option is a option group
            if(is_array($option) && count(array_filter(array_keys($option), 'is_string')) === 0) {
                foreach($option as $child) {
                    $violations = array_merge($violations, $this->validateToolbarOption($child, $settings));
                }
            }

            // case 2: option is a string or object option
            else {
                $violations = array_merge($violations, $this->validateToolbarOption($option, $settings));
            }
        }

        return $violations;
    }
}
