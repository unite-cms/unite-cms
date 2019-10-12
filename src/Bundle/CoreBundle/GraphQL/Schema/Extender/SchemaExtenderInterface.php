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
     * Return the unite definition to add to the unite.
     *
     * Don't modify $unite, just use it to access already defined types.
     * Implement App\GraphQL\Schema\Modifier\SchemaModifierInterface if you
     * want to modify the schema.
     *
     * @param Schema $schema
     *
     * @return string
     */
    public function extend(Schema $schema) : string;
}
