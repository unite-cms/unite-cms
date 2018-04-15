<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface SchemaTypeFactoryInterface
{

    /**
     * Returns true, if this factory can create a schema for the given name.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName): bool;

    /**
     * Returns the new created schema type object for the given name.
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @param Domain $domain
     * @param string $schemaTypeName
     * @return Type
     */
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, int $nestingLevel, Domain $domain = null, string $schemaTypeName): Type;
}
