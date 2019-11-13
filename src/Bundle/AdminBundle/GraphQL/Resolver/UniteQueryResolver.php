<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteQueryResolver implements FieldResolverInterface
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteQuery';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        $domain = $this->domainManager->current();

        switch ($info->fieldName) {
            case 'logs': return $domain->getLogger()->getLogs($domain, $args['before'], $args['after'] ?? null);
            case 'types': return [];
            case 'schemaFiles': return [];
            default: return null;
        }
    }
}
