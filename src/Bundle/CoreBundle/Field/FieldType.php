<?php

namespace UniteCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
    const SETTINGS = [];

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
        $options = [
            'label' => $this->getTitle($field),
            'required' => (isset($field->getSettings()->required)) ? (boolean) $field->getSettings()->required : false
        ];

        // add empty data option only if it's really explicitly allowed
        if (isset($field->getSettings()->empty_data)) {
            $options['empty_data'] = $field->getSettings()->empty_data;
        }

        return $options;
    }

    // OPTIONAL: public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {}
    // OPTIONAL: public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {}
    // OPTIONAL: public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {}
    // OPTIONAL: public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {}

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value)
    {
        return (string)$value;
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

        if (is_object($settings)) {
            $settings = get_object_vars($settings);
        }

        // Check that only allowed settings are present.
        foreach (array_keys($settings) as $setting) {
            if (!in_array($setting, static::SETTINGS)) {
                $context->buildViolation('additional_data')->atPath($setting)->addViolation();
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if (!isset($settings[$setting])) {
                $context->buildViolation('required')->atPath($setting)->addViolation();
            }
        }

        // validate required
        if (isset($settings['required']) && !is_bool($settings['required'])) {
            $context->buildViolation('noboolean_value')->atPath($setting)->addViolation();
        }

        // validate empty data
        if (isset($settings['empty_data']) && !is_string($settings['empty_data'])) {
            $context->buildViolation('nostring_value')->atPath($setting)->addViolation();
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context) {}

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        $settings['type'] = self::getType();
        $settings['label'] = $settings['label'] ?? ($field ? $field->getTitle() : null);
    }
}
