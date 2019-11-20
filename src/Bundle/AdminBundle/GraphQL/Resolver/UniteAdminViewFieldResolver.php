<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteAdminViewFieldResolver implements FieldResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteAdminViewField';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if(!$value instanceof AdminViewField) {
            throw new InvalidArgumentException(sprintf('Expect value of type %s', AdminViewField::class));
        }

        switch ($info->fieldName) {
            case 'id':
                return $value->getId();

            case 'name':
                return $value->getName();

            case 'description':
                return $value->getDescription();

            case 'type':
                return $value->getType();

            case 'list_of':
                return $value->isListOf();

            case 'non_null':
                return $value->isNonNull();

            case 'show_in_list':
                return $value->showInList();

            case 'show_in_form':
                return $value->showInForm();

            case 'form_group':
                return $value->getFormGroup();

            default: return null;
        }
    }
}
