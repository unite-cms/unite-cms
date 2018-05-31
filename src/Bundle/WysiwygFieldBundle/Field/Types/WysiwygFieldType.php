<?php

namespace UniteCMS\WysiwygFieldBundle\Field\Types;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        // Check allowed theme.
        if(!empty($settings->theme)) {
            if(!in_array($settings->theme, self::ALLOWED_THEMES)) {
                $context->buildViolation('wysiwygfield.unknown_theme')->atPath('theme')->addViolation();
            }
        }

        // Check available toolbar options.
        if(empty($settings->toolbar)) {
            $context->buildViolation('not_blank')->atPath('toolbar')->addViolation();
        }

        if(!is_array($settings->toolbar)) {
            $context->buildViolation('wysiwygfield.invalid_toolbar_definition')->atPath('toolbar')->addViolation();
        }

        // Validate toolbar options
        if($context->getViolations()->count() == 0) {
            foreach ($settings->toolbar as $option) {

                // case 1: option is a option group
                if (is_array($option) && count(array_filter(array_keys($option), 'is_string')) === 0) {
                    foreach ($option as $child) {
                        if (!in_array($child, self::ALLOWED_TOOLBAR_OPTIONS)) {

                            $path = 'toolbar';

                            if(is_string($child)) {
                                $path .= '.'.$child;
                            }

                            elseif(is_array($child) && !empty($child)) {
                                $path .= '.'.array_keys($child)[0].':'.array_values($child)[0];
                            }

                            $context->buildViolation('wysiwygfield.unknown_toolbar_option')->atPath($path)->addViolation();
                        }
                    }
                } // case 2: option is a string or object option
                else {
                    if (!in_array($option, self::ALLOWED_TOOLBAR_OPTIONS)) {

                        $path = 'toolbar';

                        if(is_string($option)) {
                            $path .= '.'.$option;
                        }

                        elseif(is_array($option) && !empty($option)) {
                            $path .= '.'.array_keys($option)[0].':'.array_values($option)[0];
                        }

                        $context->buildViolation('wysiwygfield.unknown_toolbar_option')->atPath($path)->addViolation();
                    }
                }
            }
        }
    }
}
