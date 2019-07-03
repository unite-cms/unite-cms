<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Form\MoneyType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MoneyFieldType extends TextFieldType
{
    const TYPE = "money";
    const FORM_TYPE = MoneyType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['currencies', 'description', 'form_group', 'not_empty'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'currencies' => $field->getSettings()->currencies ?? [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return [
            'value' => 0,
            'currency' => '',
        ];
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager) {
        return $schemaTypeManager->getSchemaType('MoneyField');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager) {
        return $schemaTypeManager->getSchemaType('MoneyFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return (array) $value;
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

        if(!empty($settings->currencies)) {

            if(!is_array($settings->currencies)) {
                $context->buildViolation('not_an_array')->atPath('currencies')->addViolation();
                return;
            }

            foreach($settings->currencies as $currency) {
                if(!Currencies::exists($currency)) {
                    $context->buildViolation('invalid_currency')->atPath('currencies')
                        ->setParameter('%value%', $currency)
                        ->setInvalidValue($currency)->addViolation();
                }
            }
        }
    }
}
