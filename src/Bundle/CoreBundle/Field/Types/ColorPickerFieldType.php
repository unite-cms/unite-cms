<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\WebComponentType;
use Symfony\Component\Validator\Constraints as Assert;

class ColorPickerFieldType extends FieldType
{
    const TYPE = "color";
    const FORM_TYPE = WebComponentType::class;
    const SETTINGS = ['not_empty', 'description', 'default', 'colors', 'form_group'];

    /**
     * {@inheritDoc}
     */
    public function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'tag' => 'unite-cms-color-picker-field',
                'empty_data' => '',
                'compound' => false,
                'attr' => [
                    'allowed-colors' => json_encode($field->getSettings()->colors ?? []),
                ],
            ]
        );
    }

    public function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        parent::validateSettings($settings, $context);

        if($context->getViolations()->count() > 0) {
            return;
        }

        // from https://gist.github.com/olmokramer/82ccce673f86db7cda5e#gistcomment-1656017
        $assertColor = new Assert\Regex(['pattern' => "/^(#([\da-f]{3}){1,2}|(rgb)a\((\d{1,3}%?,\s?){3}(1|0?\.\d+)\)|(rgb)\(\d{1,3}%?(,\s?\d{1,3}%?){2}\))$/i"]);

        if(!empty($settings->default)) {
            $violations = $context->getValidator()->validate($settings->default, $assertColor);
            foreach($violations as $violation) {
                $context->buildViolation($violation->getMessage())->atPath('default')->addViolation();
            }
        }

        if(!empty($settings->colors)) {
            $violations = $context->getValidator()->validate($settings->colors, new Assert\All(['constraints' => $assertColor]));
            foreach($violations as $violation) {
                $context->buildViolation($violation->getMessage())->atPath('colors')->addViolation();
            }
        }
    }
}
