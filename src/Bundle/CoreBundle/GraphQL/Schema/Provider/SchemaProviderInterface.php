<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Provider;

interface SchemaProviderInterface
{
    /**
     * Return schema definitions to initialize the schema.
     *
     * Example:
     *
     * type Foo {
     *   foo: String
     * }
     *
     * @return string
     */
    public function extend() : string;
}
