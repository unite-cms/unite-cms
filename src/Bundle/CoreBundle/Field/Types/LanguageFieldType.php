<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class LanguageFieldType extends TextFieldType
{
    const TYPE = "language";
    const FORM_TYPE = LanguageType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['languages'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            empty($field->getSettings()->languages) ? [] : [
                'choices' => $field->getSettings()->languages,
                'choice_loader' => null,
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

        if(!empty($settings->languages)) {

            if(!is_array($settings->languages)) {
                $context->buildViolation('not_an_array')->atPath('languages')->addViolation();
                return;
            }

            foreach($settings->languages as $language) {
                if(Intl::getLanguageBundle()->getLanguageName($language) === null) {
                    $context->buildViolation('invalid_language')->atPath('languages')
                        ->setParameter('%value%', $language)
                        ->setInvalidValue($language)->addViolation();
                }
            }
        }
    }
}
