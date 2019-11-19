<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Type;

use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentResultInterface;

class UniteContentResultResolver implements TypeResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, TypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteContentResult';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $context, ResolveInfo $info)
    {
        if(!$value instanceof ContentResultInterface) {
            throw new InvalidArgumentException('TypeResolver for UniteContentResult expects an ContentResultInterface value.');
        }

        return $info->schema->getType(sprintf('%sResult', $value->getType()));
    }
}
