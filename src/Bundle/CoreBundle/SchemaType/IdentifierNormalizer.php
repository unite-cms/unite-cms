<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.06.18
 * Time: 09:27
 */

namespace UniteCMS\CoreBundle\SchemaType;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;

/**
 * Normalizes identifiers between unite and GraphQL. GraphQL does not support "-", unite does not support "_".
 * Class IdentifierNormalizer
 * @package UniteCMS\CoreBundle\SchemaType
 */
class IdentifierNormalizer
{
    /**
     * Splits a GraphQL Schema into components by searching for uppercase chars. (MySchema -> ["my", "schema", "name"]).
     * @param string $schemaName
     * @return array
     */
    static function graphQLSchemaSplitter(string $schemaName) : array {
        $nameParts = preg_split('/(?=[A-Z])/', $schemaName, -1, PREG_SPLIT_NO_EMPTY);

        // If this has an Level Suffix, we need to remove it first.
        if(substr($nameParts[count($nameParts) - 1], 0, strlen('Level')) == 'Level') {
            array_pop($nameParts);
        }

        return $nameParts;
    }

    /**
     * Returns a unite identifier from a GraphQL field name (findMy_Ct -> my-ct).
     *
     * @param string $functionName
     * @return string
     */
    static function fromGraphQLFieldName(string $functionName) : string {

        $parts = static::graphQLSchemaSplitter($functionName);

        if(empty($parts)) {
            return '';
        }

        return str_replace('_', '-', strtolower($parts[1]));
    }

    /**
     * Returns a unite identifier from a GraphQL schema name (CtContent -> ct).
     *
     * @param string $schemaName
     * @return string
     */
    static function fromGraphQLSchema(string $schemaName) : string {

        $parts = static::graphQLSchemaSplitter($schemaName);

        if(empty($parts)) {
            return '';
        }

        return str_replace('_', '-', strtolower($parts[0]));
    }

    /**
     * Normalizes array keys from graphQL data argument to unite data array.
     *
     * @param array $data
     * @param FieldTypeManager $fieldTypeManager
     * @param Fieldable $fieldable
     * @return array
     */
    static function fromGraphQLData(array $data, FieldTypeManager $fieldTypeManager, Fieldable $fieldable) : array {
        $normalizedData = [];

        foreach ($data as $key => $value) {

            $normalizedKey = str_replace('_', '-', $key);

            // if this would be an known field, we need to normalize the key.
            if($fieldable->getFields()->containsKey($normalizedKey)) {

                /*** @var FieldableField $fieldableField */
                $fieldableField = $fieldable->getFields()->get($normalizedKey);

                /*** @var FieldTypeInterface $fieldType */
                $fieldType = $fieldTypeManager->getFieldType($fieldableField->getType());

                // If this field type could have children.
                if($fieldType instanceof NestableFieldTypeInterface) {
                    if(is_array($value)) {
                        foreach($value as $rowNumber => $row) {
                            $value[$rowNumber] = static::fromGraphQLData($row, $fieldTypeManager, $fieldType->getNestableFieldable($fieldableField));
                        }
                    }
                }

                $normalizedData[$normalizedKey] = $value;

            // If this is not a known field, just pass the data to the normalized array.
            } else {
                $normalizedData[$key] = $value;
            }
        }

        return $normalizedData;
    }

    /**
     * Returns a GraphQL identifier from a given resource.
     * @param mixed $resource, Can be an object that implements getIdentifier() or a string.
     * @return string
     */
    static function graphQLIdentifier($resource) : string {

        $identifier = '';

        if(is_string($resource)) {
            $identifier = $resource;
        }

        else if(is_object($resource) && method_exists($resource, 'getIdentifier')) {
            $identifier = $resource->getIdentifier();
        }

        return str_replace('-', '_', $identifier);
    }

    /**
     * Returns a GraphQL type from a given resource.
     * @param mixed $resource, Can be an object that implements getIdentifier() or a string.
     * @param string $suffix, Override automatically detected suffix with this one.
     *
     * @return string
     */
    static function graphQLType($resource, $suffix = null) : string {

        if($suffix === null) {
            if($resource instanceof ContentType) {
                $suffix = 'Content';
            }

            if($resource instanceof SettingType) {
                $suffix = 'Setting';
            }
        }

        return ucfirst(static::graphQLIdentifier($resource)).$suffix;
    }
}