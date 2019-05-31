<?php

namespace UniteCMS\CoreBundle\Field;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface FieldTypeInterface
{
    static function getType(): string;

    /**
     * Returns the graphQL schema type for queries. This method must either return a ScalarType or a registered custom
     * type from schemaTypeManager.
     *
     * Example 1:
     *   return GraphQL\Type\Definition\Type::string();
     *
     * Example 2:
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldType', $this->uniteCMSManager->getDomain());
     *
     * @param FieldableField $field
     * @param SchemaTypeManager $schemaTypeManager
     * @return Type|null|array
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager);

    /**
     * Returns the graphQL schema type for mutation inputs. This method must either return a ScalarType or a registered
     * custom type from schemaTypeManager.
     *
     * Example 1:
     *   return GraphQL\Type\Definition\Type::string();
     *
     * Example 2:
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $this->uniteCMSManager->getDomain());
     *
     * @param FieldableField $field
     * @param SchemaTypeManager $schemaTypeManager
     * @return Type|null
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager);

    /**
     * Returns the class name of the form, used to process data during graphQL mutations and for admin form rendering.
     *
     * @param FieldableField $field
     * @return string
     */
    function getFormType(FieldableField $field): string;

    /**
     * Returns options that get passed to the form.
     *
     * @param FieldableField $field
     * @return array
     */
    function getFormOptions(FieldableField $field): array;

    /**
     * Returns the default value for this field. This method will be called when a new fieldablecontent is passed to
     * the fieldableform builder.
     *
     * @param FieldableField $field
     * @return null|mixed
     */
    function getDefaultValue(FieldableField $field);

    /**
     * Get the title for this field.
     *
     * @param FieldableField $field
     * @return string
     */
    function getTitle(FieldableField $field): string;

    /**
     * Get the identifier for this field.
     *
     * @param FieldableField $field
     * @return string
     */
    function getIdentifier(FieldableField $field): string;

    /**
     * Callback for resolving data for the graphQL API. A simple solution would be to just return the value.
     *
     * @param FieldableField $field
     * @param $value
     * @param FieldableContent $content
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info);

    /**
     * A callback to allow the field type to validate the field settings.
     *
     * @param FieldableFieldSettings $settings
     * @param ExecutionContextInterface $context
     *
     * @return ConstraintViolation[]
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context);

    /**
     * A callback to allow the field type to validate the data for a given fieldable.
     *
     * @param FieldableField $field
     * @param array $data
     * @param ExecutionContextInterface $context
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context);

    /**
     * This method will be called in situations like after form submit, before validation.
     *
     * It allows the field to alter the data array based on the (old) content object, the new data and a fieldableField
     * object. NOTE: It is generally not a good idea to alter data from other fields than this one.
     *
     * &$data should only contain the data of the current field (this is important for nestable fields).
     * $rootData is the full content data.
     *
     * @param FieldableField $field
     * @param array $data
     * @param FieldableContent $content
     * @param array $rootData
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData);

    /**
     * Allows the field to alter defined settings. The field can always overrule configured settings.
     * Allowed keys are: label, type, settings, assets.
     *
     * Assets can be defined as:
     * [ 'css' => 'main.css', 'package' => 'UniteCMSStorageBundle' ]
     * [ 'js' => 'main.js', 'package' => 'UniteCMSStorageBundle' ]
     * [ 'css' => 'https://example.com/main.css' ]
     * [ 'js' => 'https://example.com/main.js' ]
     *
     * @param array $settings
     * @param FieldableField $field
     * @param FieldTypeManager $fieldTypeManager
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null);
}
