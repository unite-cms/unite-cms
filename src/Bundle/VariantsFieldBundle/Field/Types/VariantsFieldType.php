<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.08.18
 * Time: 14:08
 */

namespace UniteCMS\VariantsFieldBundle\Field\Types;


use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UniteCMS\VariantsFieldBundle\Form\VariantsFormType;
use UniteCMS\VariantsFieldBundle\Model\Variants;

class VariantsFieldType extends FieldType implements NestableFieldTypeInterface
{
    const TYPE                      = "variants";
    const FORM_TYPE                 = VariantsFormType::class;
    const SETTINGS                  = ['variants'];
    const REQUIRED_SETTINGS         = ['variants'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        // Configure the variants from type.
        return array_merge(parent::getFormOptions($field), [
            'variants' => self::getNestableFieldable($field),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Let parent validate allowed and required root level settings.
        parent::validateSettings($settings, $context);
        if($context->getViolations()->count() > 0) {
            return;
        }

        // Variants must be defined as array.
        if(!is_array($settings->variants)) {
            $context->buildViolation('variantsfield.not_an_array')->atPath('variants')->addViolation();
            return;
        }

        // Variants must not be empty.
        if(empty($settings->variants)) {
            $context->buildViolation('required')->atPath('variants')->addViolation();
            return;
        }

        // Validate each variant.
        foreach($settings->variants as $delta => $variant) {
            $this->validateVariant($variant, $delta, $context);
        }
    }

    /**
     * Validates a single variant setting.
     * @param $variant
     * @param $delta
     * @param ExecutionContextInterface $context
     */
    function validateVariant($variant, $delta, ExecutionContextInterface $context) {

        $path = 'variants[' . $delta . '].';

        // Check that only allowed settings are present.
        foreach (array_keys($variant) as $setting) {
            if (!in_array($setting, ['title', 'identifier', 'description', 'icon', 'fields'])) {
                $context->buildViolation('additional_data')->atPath($path . $setting)->addViolation();
            }
        }

        // Check that all required settings are present.
        foreach (['title', 'identifier', 'fields'] as $setting) {
            if (!isset($variant[$setting])) {
                $context->buildViolation('required')->atPath($path . $setting)->addViolation();
            }
        }

        if($context->getViolations()->count() > 0) {
            return;
        }

        // Check that variant identifier is not "type".
        if($variant['identifier'] === 'type') {
            $context->buildViolation('reserved_identifier')->atPath($path . 'identifier')->addViolation();
        }

        // Check that fields is an array.
        if(!is_array($variant['fields'])) {
            $context->buildViolation('variantsfield.not_an_array')->atPath($path . 'fields')->addViolation();
            return;
        }

        // TODO: Validate fields
    }

    /**
     * {@inheritdoc}
     */
    static function getNestableFieldable(FieldableField $field): Fieldable
    {
        return new Variants(
            $field->getSettings()->variants,
            $field->getIdentifier(),
            $field->getEntity()
        );
    }
}