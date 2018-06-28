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
        return array_merge(
            parent::getFormOptions($field),
            [
                'attr' => [
                    'data-options' => json_encode([
                        'placeholder' => $field->getSettings()->placeholder ?? '',
                        'toolbar' => $field->getSettings()->toolbar ?? static::DEFAULT_TOOLBAR,
                        'heading' => $field->getSettings()->heading ?? Static::DEFAULT_HEADING,
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
}
