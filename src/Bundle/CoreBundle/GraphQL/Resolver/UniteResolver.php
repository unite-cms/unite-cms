<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\UniteCMSCoreBundle;

class UniteResolver implements FieldResolverInterface
{
    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Query' || $typeName === 'Mutation';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {
        return $info->fieldName === 'version' ? UniteCMSCoreBundle::UNITE_VERSION : null;
    }
}
