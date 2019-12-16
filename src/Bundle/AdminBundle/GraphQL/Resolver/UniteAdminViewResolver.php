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
        foreach($typeDefinitionNode->interfaces as $interface) {
            if ($interface->name->value === 'UniteAdminView') {
                return true;
            }
        }
        return false;
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

            case 'titlePattern':
                return $value->getTitlePattern();

            case 'fragment':
                return $value->getFragment();

            case 'fields':
                return $value->getFields();

            case 'category':
                return $value->getCategory();

            case 'permissions':
                return $value->getPermissions();

            case 'groups':
                return $value->getGroups();

            default:
                return $value->getConfig()->get($info->fieldName);
        }
    }
}
