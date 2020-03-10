<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;

class RemoveUnusedTypesModifier implements SchemaModifierInterface
{
    const CLIENT_SAFE_DIRECTIVES = ['named'];

    /**
     * @param array $types
     * @param array $interfaces
     * @param Type $type
     */
    protected function findAllReachableTypes(array &$types, array &$interfaces, Type $type) : void {
        foreach($type->getFields() as $field) {

            $type = $field->getType();
            if($type instanceof WrappingType) {
                $type = $type->getWrappedType(true);
            }

            if(!in_array($type->name, $types)) {
                $types[] = $type->name;

                if($type instanceof ObjectType || $type instanceof InputObjectType || $type instanceof InterfaceType) {
                    $this->findAllReachableTypes($types, $interfaces, $type);
                }

                if($type instanceof ObjectType) {
                    foreach($type->getInterfaces() as $interface) {

                        if(!in_array($interface->name, $types)) {
                            $types[] = $interface->name;
                            $this->findAllReachableTypes($types, $interfaces, $interface);
                        }
                    }
                }

                if($type instanceof UnionType) {
                    foreach($type->getTypes() as $unionType) {

                        if($unionType instanceof ObjectType) {
                            if(!in_array($unionType->name, $types)) {
                                $types[] = $unionType->name;
                                $this->findAllReachableTypes($types, $interfaces, $unionType);
                            }
                        }
                    }
                }

                if($type instanceof InterfaceType) {
                    $interfaces[] = $type;
                }

            }

            if(!empty($field->config['args'])) {
                foreach ($field->config['args'] as $arg) {
                    $type = $arg['type'];
                    if ($type instanceof WrappingType) {
                        $type = $type->getWrappedType(true);
                    }

                    if (!in_array($type->name, $types)) {
                        $types[] = $type->name;

                        if ($type instanceof ObjectType || $type instanceof InputObjectType) {
                            $this->findAllReachableTypes($types,$interfaces,  $type);
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function modify(DocumentNode &$document, Schema $schema, ExecutionContext $context) : void
    {
        $usedTypes = [$schema->getQueryType()->name];
        $usedInterfaces = [];

        $this->findAllReachableTypes($usedTypes, $usedInterfaces, $schema->getQueryType());

        if($schema->getMutationType()) {
            $usedTypes[] = $schema->getMutationType()->name;
            $this->findAllReachableTypes($usedTypes, $usedInterfaces, $schema->getMutationType());
        }

        if($schema->getSubscriptionType()) {
            $usedTypes[] = $schema->getSubscriptionType()->name;
            $this->findAllReachableTypes($usedTypes, $usedInterfaces, $schema->getSubscriptionType());
        }

        foreach($usedInterfaces as $interface) {
            foreach($schema->getPossibleTypes($interface) as $possibleType) {
                $usedTypes[] = $possibleType->name;
                $this->findAllReachableTypes($usedTypes, $usedInterfaces, $possibleType);
            }
        }

        // Modify the schema document and remove all hidden objects and fields.
        $document = Visitor::visit($document, [
            'enter' => function ($node, $key, $parent, $path, $ancestors) use ($usedTypes) {
                if(in_array($node->kind, [
                    NodeKind::OBJECT_TYPE_DEFINITION,
                    NodeKind::INTERFACE_TYPE_DEFINITION,
                    NodeKind::DIRECTIVE_DEFINITION,
                    NodeKind::INPUT_OBJECT_TYPE_DEFINITION
                ])) {
                    if(!in_array($node->name->value, $usedTypes)) {

                        // Allow client safe directives
                        if($node->kind === NodeKind::DIRECTIVE_DEFINITION && in_array($node->name->value, static::CLIENT_SAFE_DIRECTIVES)) {
                            return null;
                        }

                        return Visitor::removeNode();
                    }
                }

                return null;
            }
        ]);
    }
}
