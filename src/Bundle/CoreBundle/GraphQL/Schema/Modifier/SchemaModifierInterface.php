<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;


use UniteCMS\CoreBundle\Domain\ExecutionContext;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;

/**
 * Class SchemaModifierInterface
 *
 * @package App\GraphQL\Schema\Modifier
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
     * @return void
     */
    public function modify(DocumentNode &$document, Schema $schema) : void;
}
