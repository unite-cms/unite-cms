<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use GraphQL\Type\Schema;

/**
 * Interface SchemaExtenderInterface
 *
 * @package App\GraphQL\Schema\Extender
 */
interface SchemaExtenderInterface
{
    const EXTENDER_BEFORE = 'before';
    const EXTENDER_AFTER = 'after';

    /**
     * Return new or extended definitions to add to the schema.
     *
     * Don't modify $unite, just use it to access already defined types.
     * Implement App\GraphQL\Schema\Modifier\SchemaModifierInterface if you
     * want to modify the schema.
     *
     * Example:
     *
     * type Foo {
     *   foo: String
     * }
     *
     * extend Baa {
     *   foo: String
     * }
     *
     * @param Schema $schema
     *
     * @return string
     */
    public function extend(Schema $schema) : string;
}
