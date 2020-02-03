<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\Printer;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

/**
 * Get all extend definitions from schema and add them as schema extensions.
 */
class DomainSchemaExtender implements SchemaExtenderInterface
{
    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    /**
     * @param SchemaManager $schemaManager
     */
    public function __construct(SchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * @inheritDoc
     */
    public function extend(Schema $schema, ExecutionContext $context): string
    {
        $extension = '';
        foreach($this->schemaManager->getBaseSchemaDefinition()->definitions as $definition) {
            if($definition instanceof TypeExtensionNode) {
                $extension .= Printer::doPrint($definition);
            }
        }

        return $extension;
    }
}
