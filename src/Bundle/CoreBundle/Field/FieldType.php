<?php

namespace UniteCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintViolation;
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
        return [
            'label' => $this->getTitle($field),
            'required' => false,
        ];
    }

    // OPTIONAL: public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data) {}
    // OPTIONAL: public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {}
    // OPTIONAL: public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {}
    // OPTIONAL: public function onHardDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {}

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
     * @param FieldableField $field
     * @param FieldableFieldSettings $settings
     * @return array
     */
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        $violations = [];

        if (is_object($settings)) {
            $settings = get_object_vars($settings);
        }

        // Check that only allowed settings are present.
        foreach (array_keys($settings) as $setting) {
            if (!in_array($setting, static::SETTINGS)) {
                $violations[] = new ConstraintViolation(
                    'validation.additional_data',
                    'validation.additional_data',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if (!isset($settings[$setting])) {
                $violations[] = new ConstraintViolation(
                    'validation.required',
                    'validation.required',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
    {
        return [];
    }

    protected function createViolation($field, $message, $messageTemplate = null, $parameters = [], $root = null, string $propertyPath = null, $invalidValue = null, $plural = null)
    {

        if (!$messageTemplate) {
            $messageTemplate = $message;
        }

        if (!$propertyPath) {
            $propertyPath = '[' . $this->getIdentifier($field) . ']';
        }

        return new ConstraintViolation($message, $messageTemplate, $parameters, $root, $propertyPath, $invalidValue, $plural);
    }
}
