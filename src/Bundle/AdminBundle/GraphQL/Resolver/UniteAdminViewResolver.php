<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteAdminViewResolver implements FieldResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteAdminView';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if(!$value instanceof AdminView) {
            throw new InvalidArgumentException(sprintf('Expect value of type %s', AdminView::class));
        }

        switch ($info->fieldName) {
            case 'id':
                return $value->getId();

            case 'type':
                return $value->getType();

            case 'name':
                return $value->getName();

            case 'fragment':
                return $value->getFragment();

            case 'fields':
                return $value->getFields();

            case 'category':
                return $value->getCategory();

            case 'limit':
                return $value->getLimit();

            case 'orderBy':
                return $value->getOrderBy();

            case 'filter':
                return $value->getFilter();

            default: return null;
        }
    }
}