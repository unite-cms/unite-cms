<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;

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

        switch ($info->fieldName) {
            case 'unite':
                return $info->parentType->name === 'Query' ? [
                '_version' => null,
                'me' => null,
            ] : [
                '_version' => null,
                'generateJWT' => null,
            ];
            default: return null;
        }
    }
}
