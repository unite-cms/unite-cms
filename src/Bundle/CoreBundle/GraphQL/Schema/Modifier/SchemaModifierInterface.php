<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;


use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;

/**
 * Class SchemaModifierInterface
 */
interface SchemaModifierInterface
{

    /**
     * Modify the documentNode for this schema directly after it was built.
     *
     * This will be executed after all schema extensions and before it will be
     * cached.
     *
     * @param DocumentNode &$document
     * @param Schema $schema
     *
     * @param ExecutionContext $executionContext
     * @return void
     */
    public function modify(DocumentNode &$document, Schema $schema, ExecutionContext $executionContext) : void;
}
