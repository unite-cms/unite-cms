<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\CoreBundle\GraphQL\Resolver\Type\TypeResolverInterface;

class UniteAdminViewTypeResolver implements TypeResolverInterface
{


    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, TypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteAdminView';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $context, ResolveInfo $info)
    {

        if(!$value instanceof AdminView) {
            throw new InvalidArgumentException('TypeResolver for UniteAdminView expects an AdminView value.');
        }

        return $info->schema->getType($value->getReturnType());
    }
}
