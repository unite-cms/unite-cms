<?php

namespace UniteCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldType', $this->uniteCMSManager->getDomain(), $nestingLevel);
     *
     * @param FieldableField $field
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

    /**
     * Returns the graphQL schema type for mutation inputs. This method must either return a ScalarType or a registered
     * custom type from schemaTypeManager.
     *
     * Example 1:
     *   return GraphQL\Type\Definition\Type::string();
     *
     * Example 2:
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $this->uniteCMSManager->getDomain(), $nestingLevel);
     *
     * @param FieldableField $field
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

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
     * @return mixed
     */
    function resolveGraphQLData(FieldableField $field, $value);

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
     * Return optional configuration that gets passed to the javascript field component when a content type view gets rendered.
     *
     * @param FieldableField $field
     * @return array
     */
    function getViewFieldConfig(FieldableField $field = null) : array;

    /**
     * Return optional assets css and js assets, that get included when rendering the view. Assets can de defined as:
     *
     * [ 'css' => 'main.css', 'package' => 'UniteCMSStorageBundle' ]
     * [ 'js' => 'main.js', 'package' => 'UniteCMSStorageBundle' ]
     * [ 'css' => 'https://example.com/main.css' ]
     * [ 'js' => 'https://example.com/main.js' ]
     *
     * @param FieldableField $field
     * @return array
     */
    function getViewFieldAssets(FieldableField $field = null) : array;
}
