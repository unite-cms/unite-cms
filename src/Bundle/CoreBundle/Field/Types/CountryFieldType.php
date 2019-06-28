<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class CountryFieldType extends TextFieldType
{
    const TYPE = "country";
    const FORM_TYPE = CountryType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['countries', 'not_empty', 'description', 'default', 'form_group'];

    function getFormOptions(FieldableField $field): array
    {
        $choice_settings = [];

        if(!empty($field->getSettings()->countries)) {

            $choice_settings = [
                'choices' => [],
                'choice_loader' => null,
            ];

            foreach($field->getSettings()->countries as $country) {
                $choice_settings['choices'][Countries::getName($country)] = $country;
            }
        }

        return array_merge(parent::getFormOptions($field), $choice_settings);
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

        if(!empty($settings->countries)) {

            if(!is_array($settings->countries)) {
                $context->buildViolation('not_an_array')->atPath('countries')->addViolation();
                return;
            }

            foreach($settings->countries as $country) {
                if(Countries::getName($country) === null) {
                    $context->buildViolation('invalid_country')->atPath('countries')
                        ->setParameter('%value%', $country)
                        ->setInvalidValue($country)->addViolation();
                }
            }
        }
    }
}
