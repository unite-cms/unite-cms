<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;

class RemoveUnusedTypesModifier implements SchemaModifierInterface
{

    /**
     * @param array $types
     * @param Type $type
     */
    protected function findAllReachableTypes(array &$types, Type $type) : void {
        foreach($type->getFields() as $field) {

            $type = $field->getType();
            if($type instanceof WrappingType) {
                $type = $type->getWrappedType(true);
            }

            if(!in_array($type->name, $types)) {
                $types[] = $type->name;

                if($type instanceof ObjectType || $type instanceof InputObjectType) {
                    $this->findAllReachableTypes($types, $type);
                }

                if($type instanceof ObjectType) {
                    foreach($type->getInterfaces() as $interface) {

                        if(!in_array($interface->name, $types)) {
                            $types[] = $interface->name;
                            $this->findAllReachableTypes($types, $interface);
                        }
                    }
                }

                if($type instanceof UnionType) {
                    foreach($type->getTypes() as $unionType) {

                        if($unionType instanceof ObjectType) {
                            if(!in_array($unionType->name, $types)) {
                                $types[] = $unionType->name;
                                $this->findAllReachableTypes($types, $unionType);
                            }
                        }
                    }
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
                            $this->findAllReachableTypes($types, $type);
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function modify(DocumentNode &$document, Schema $schema) : void
    {
        $usedTypes = [$schema->getQueryType()->name];
        $this->findAllReachableTypes($usedTypes, $schema->getQueryType());

        if($schema->getMutationType()) {
            $usedTypes[] = $schema->getMutationType()->name;
            $this->findAllReachableTypes($usedTypes, $schema->getMutationType());
        }

        if($schema->getSubscriptionType()) {
            $usedTypes[] = $schema->getSubscriptionType()->name;
            $this->findAllReachableTypes($usedTypes, $schema->getSubscriptionType());
        }

        foreach($document->definitions as $d_key => $definition) {

            if(!in_array($definition->name->value, $usedTypes)) {
                unset($document->definitions[$d_key]);
            }
        }
    }
}
