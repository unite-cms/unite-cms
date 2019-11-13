<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class UniteMutationResolver implements FieldResolverInterface
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    public function __construct(DomainManager $domainManager, SchemaManager $schemaManager)
    {
        $this->domainManager = $domainManager;
        $this->schemaManager = $schemaManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteMutation';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        switch ($info->fieldName) {
            case 'updateSchemaFiles':
                // TODO
                return false;

            default: return null;
        }
    }
}
