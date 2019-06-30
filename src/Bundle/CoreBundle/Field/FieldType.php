<?php

namespace UniteCMS\CoreBundle\Field;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

/**
 * An abstract base field type, that allows to easily implement custom field types.
 */
abstract class FieldType implements FieldTypeInterface
{
    /**
     * The unique type identifier for this field type.
     */
    const TYPE = "";

    /**
     * The Symfony form type for this field. Can also be a custom form type.
     */
    const FORM_TYPE = "";

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['not_empty', 'description', 'default', 'form_group'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = [];

    /**
     * {@inheritdoc}
     */
    static function getType(): string
    {
        return static::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    function getFormType(FieldableField $field): string
    {
        return static::FORM_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return [
            'label' => $this->getTitle($field),
            'required' => false,
            'not_empty' => (isset($field->getSettings()->not_empty)) ? (boolean) $field->getSettings()->not_empty : false,
            'description' => (isset($field->getSettings()->description)) ? (string) $field->getSettings()->description : '',
            'form_group' => (isset($field->getSettings()->form_group)) ? $field->getSettings()->form_group : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return $field->getSettings()->default ?? null;
    }

    // OPTIONAL: public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {}
    // OPTIONAL: public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {}
    // OPTIONAL: public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {}
    // OPTIONAL: public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {}

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return ($value === null) ? null : (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    function getTitle(FieldableField $field): string
    {
        return $field->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    function getIdentifier(FieldableField $field): string
    {
        return $field->getIdentifier();
    }

    /**
     * Basic settings validation based on self::SETTINGS and self::REQUIRED_SETTINGS constants. More sophisticated
     * validation should be done in child implementations.
     *
     * @param FieldableFieldSettings $settings
     * @param ExecutionContextInterface $context
     *
     * @return array
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        $violations = [];
        $settingsArray = $settings ? get_object_vars($settings) : [];

        // Check that only allowed settings are present.
        foreach (array_keys($settingsArray) as $setting) {
            if (!in_array($setting, static::SETTINGS)) {
                $context->buildViolation('additional_data')->atPath($setting)->addViolation();
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if (!isset($settingsArray[$setting])) {
                $context->buildViolation('required')->atPath($setting)->addViolation();
            }
        }

        // validate empty data is boolean
        if (!empty($settingsArray['not_empty'])) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settingsArray['not_empty'], new Assert\Type(['type' => 'boolean', 'message' => 'noboolean_value']))
            );
        }

        // validate description length
        if (!empty($settingsArray['description'])) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settingsArray['description'], [
                    new Assert\Type(['type' => 'string', 'message' => 'nostring_value']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'too_long'])
                ])
            );
        }

        // validate form_group is false or string
        if(!empty($settingsArray['form_group'])) {
            if(!(is_bool($settingsArray['form_group']) && $settingsArray['form_group'] === false) && !is_string($settingsArray['form_group'])) {
                $context->buildViolation('invalid_form_group')->atPath($setting)->addViolation();
            }
        }

        if (!empty($settingsArray['default'])) {
            $this->validateDefaultValue($settingsArray['default'], $settings, $context);
        }

        return $violations;
    }

    /**
     * Validates the default value if it is set.
     * @param $value
     * @param FieldableFieldSettings $settings
     * @param ExecutionContextInterface $context
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'string', 'message' => 'invalid_initial_data']))
        );
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context) {}

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData) {}

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        $settings['type'] = self::getType();
        $settings['label'] = $settings['label'] ?? ($field ? $field->getTitle() : null);
    }
}
