<?php

namespace UniteCMS\CoreBundle\SchemaType;

use GraphQL\Type\Definition\Type;

/**
 * Allows to alter existing schema types. The method "supports" should return true, if this alteration should alter
 * the given schemaType.
 */
interface SchemaTypeAlterationInterface
{
    /**
     * Returns true, if this alteration should alter the given schema type.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName) : bool;

    /**
     * Alters the schema type.
     *
     * @param Type $schemaType
     */
    public function alter(Type $schemaType) : void;
}
