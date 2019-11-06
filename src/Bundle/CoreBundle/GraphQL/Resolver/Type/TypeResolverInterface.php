<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Type;

use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Interface TypeResolverInterface
 *
 * @package App\GraphQL\Resolver
 */
interface TypeResolverInterface
{
    /**
     * Return true, if this resolver supports the given type.
     *
     * @param string $typeName
     * @param UnionTypeDefinitionNode|InterfaceTypeDefinitionNode|TypeDefinitionNode $typeDefinitionNode
     *
     * @return bool
     */
    public function supports(string $typeName, TypeDefinitionNode $typeDefinitionNode) : bool;

    /**
     * Resolve a graphql union / interface type to a real type.
     *
     * @param mixed $value
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    public function resolve($value, $context, ResolveInfo $info);
}
