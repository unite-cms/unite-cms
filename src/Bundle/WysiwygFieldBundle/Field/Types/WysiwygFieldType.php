<?php

namespace UniteCMS\WysiwygFieldBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\WysiwygFieldBundle\Form\WysiwygType;

class WysiwygFieldType extends FieldType
{
    const TYPE                      = "wysiwyg";
    const FORM_TYPE                 = WysiwygType::class;
    const SETTINGS                  = ['toolbar', 'heading', 'placeholder'];
    const REQUIRED_SETTINGS         = [];
    const ALLOWED_TOOLBAR           = ['|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'];
    const ALLOWED_HEADING           = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'code'];
    const DEFAULT_TOOLBAR           = ['bold', 'italic', 'link'];
    const DEFAULT_HEADING           = ['p', 'h1', 'h2'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $toolbar = $field->getSettings()->toolbar ?? static::DEFAULT_TOOLBAR;
        $heading = $field->getSettings()->heading ?? static::DEFAULT_HEADING;

        if(!empty($heading)) {
            $heading = array_map(function($option){

                // If the option was defined as full heading.
                if(is_array($option)) {
                    $heading_option['view'] = $option['view'];
                    $heading_option['model'] = $option['model'];
                    $heading_option['title'] = $option['title'] ?? ucfirst($heading_option['model']);
                    if(!empty($option['class'])) {
                        $heading_option['class'] = $option['class'];
                    }

                // If the option was defined as simple string.
                } else {
                    $heading_option = [
                        'view' => $option,
                        'model' => null,
                    ];
                }

                $compare_view = is_string($heading_option['view']) ? $heading_option['view'] : $heading_option['view']['name'];

                if($compare_view === 'p') {
                    $heading_option['model'] = $heading_option['model'] ?? 'paragraph';
                    $heading_option['class'] = $heading_option['class'] ?? 'ck-heading_paragraph';
                    $heading_option['title'] = $heading_option['title'] ?? 'Paragraph';
                }

                if(preg_match('/^h([1-6]+)$/', $compare_view, $matches)) {
                    $heading_option['model'] = $heading_option['model'] ?? 'heading'.$matches[1];
                    $heading_option['title'] = $heading_option['title'] ?? 'Heading '.$matches[1];
                    $heading_option['class'] = $heading_option['class'] ?? 'ck-heading_heading'.$matches[1];
                }

                if(empty($heading_option['model'])) {
                    $heading_option['model'] = $heading_option['view'];
                }

                if(empty($heading_option['title'])) {
                    $heading_option['title'] = ucfirst($heading_option['model']);
                }

                return $heading_option;

            }, $heading);
            $toolbar = array_merge(['heading', '|'], $toolbar);
        }

        return array_merge(
            parent::getFormOptions($field),
            [
                'attr' => [
                    'data-options' => json_encode([
                        'placeholder' => $field->getSettings()->placeholder ?? '',
                        'toolbar' => $toolbar,
                        'heading' => $heading,
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

        // Validate toolbar options
        if(!empty($settings->toolbar)) {
            if (!is_array($settings->toolbar)) {
                $context->buildViolation('wysiwygfield.not_an_array')->atPath('toolbar')->addViolation();
            }
            if ($context->getViolations()->count() == 0) {
                foreach ($settings->toolbar as $option) {

                    if(!is_string($option)) {
                        $context->buildViolation('wysiwygfield.not_an_array')->atPath('toolbar')->addViolation();
                    }

                    else if (!in_array($option, self::ALLOWED_TOOLBAR)) {
                        $context->buildViolation('wysiwygfield.unknown_option')->atPath('toolbar.'.$option)->addViolation();
                    }
                }
            }
        }

        // Validate heading options
        if(!empty($settings->heading)) {
            if (!is_array($settings->heading)) {
                $context->buildViolation('wysiwygfield.not_an_array')->atPath('heading')->addViolation();
            }
            if ($context->getViolations()->count() == 0) {
                foreach ($settings->heading as $option) {

                    // Options can be defined as simple strings or as full heading objects.
                    if(is_array($option)) {

                        // Make sure, that all required heading options are set.
                        if(!array_key_exists('view', $option) || !array_key_exists('model', $option)) {
                            $context->buildViolation('wysiwygfield.invalid_heading_definition')->atPath('heading')->addViolation();
                            return;
                        }

                        // Make sure, that heading view is an array with the required name property.
                        if(!is_array($option['view']) || empty($option['view']['name'])) {
                            $context->buildViolation('wysiwygfield.invalid_heading_definition')->atPath('heading')->addViolation();
                            return;
                        }

                        // Make sure, that there are only allowed heading options defined.
                        foreach($option as $key => $value) {
                            if(!in_array($key, ['view', 'model', 'title'])) {
                                $context->buildViolation('wysiwygfield.invalid_heading_definition')->atPath('heading.'.$option['view']['name'])->addViolation();
                                return;
                            }
                        }

                        // Make sure, that there are only allowed view options defined.
                        foreach($option['view'] as $key => $value) {
                            if(!in_array($key, ['name', 'classes'])) {
                                $context->buildViolation('wysiwygfield.invalid_heading_definition')->atPath('heading.'.$option['view']['name'])->addViolation();
                                return;
                            }
                        }

                        $option = $option['view']['name'];
                    }

                    if(!is_string($option)) {
                        $context->buildViolation('wysiwygfield.not_an_array')->atPath('heading')->addViolation();
                    }

                    else if (!in_array($option, self::ALLOWED_HEADING)) {
                        $context->buildViolation('wysiwygfield.unknown_option')->atPath('heading.'.$option)->addViolation();
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function getViewFieldAssets(FieldableField $field = null) : array {
        return [
            [ 'js' => 'main.js', 'package' => 'UniteCMSWysiwygFieldBundle' ],
            [ 'css' => 'main.css', 'package' => 'UniteCMSWysiwygFieldBundle' ],
        ];
    }
}
