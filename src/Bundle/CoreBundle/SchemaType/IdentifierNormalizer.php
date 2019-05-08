<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.06.18
 * Time: 09:27
 */

namespace UniteCMS\CoreBundle\SchemaType;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\SettingType;

/**
 * Normalizes identifiers between unite and GraphQL.
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
     * Returns a unite identifier from a GraphQL field name (findMy_Ct -> my_ct).
     *
     * @param string $functionName
     * @return string
     */
    static function fromGraphQLFieldName(string $functionName) : string {

        $parts = static::graphQLSchemaSplitter($functionName);

        if(empty($parts)) {
            return '';
        }

        return strtolower($parts[1]);
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

        return strtolower($parts[0]);
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

        return (string)$identifier;
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

            if($resource instanceof DomainMemberType) {
                $suffix = 'Member';
            }
        }

        return ucfirst(static::graphQLIdentifier($resource)).$suffix;
    }
}