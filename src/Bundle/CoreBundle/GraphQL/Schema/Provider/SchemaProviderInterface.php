<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Provider;

interface SchemaProviderInterface
{
    /**
     * Return schema definitions to initialize the schema.
     *
     * IMPORTANT: You cannot depend on the current user. The schema providers
     * can be used BEFORE authentication. If you want to show / hide types or
     * fields based on the current user, use the @hide() directive or implement
     * a custom schema extender or schema modifier.
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
