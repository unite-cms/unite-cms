<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;

/**
 * Interface FieldResolverInterface
 *
 * @package App\GraphQL\Resolver
 */
interface FieldResolverInterface
{

    /**
     * Return true, if this resolver supports the given type.
     *
     * @param string $typeName
     * @param ObjectTypeDefinitionNode $typeDefinitionNode
     *
     * @return bool
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode) : bool;

    /**
     * Resolve a graphql field during execution.
     *
     * Use the field/type info and args to resolve the field data from value.
     *
     * @param mixed $value
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    public function resolve($value, $args, ExecutionContext $context, ResolveInfo $info);
}
