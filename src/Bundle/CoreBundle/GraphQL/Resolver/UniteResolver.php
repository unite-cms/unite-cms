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

        if($info->fieldName !== 'unite') {
            return null;
        }

        if($info->parentType->name === 'Query') {
            return [
                'version' => UniteCMSCoreBundle::UNITE_VERSION,
            ];
        }

        else if($info->parentType->name === 'Mutation') {
            return [
                'login' => null,
            ];
        }

        return null;
    }
}
