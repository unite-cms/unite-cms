<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;

use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Visitor;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;

class DecorateFieldableModifier implements SchemaModifierInterface
{
    const FIELDABLE_INTERFACE = 'UniteFieldable';
    const CONTENT_INTERFACES = ['UniteContent', 'UniteUser', 'UniteSingleContent', 'UniteEmbeddedContent'];

    /**
     * {@inheritDoc}
     */
    public function modify(DocumentNode &$document, Schema $schema) : void
    {
        // Add a fieldable interface to all unite content types.
        $document = Visitor::visit($document, [
            'enter' => [

                NodeKind::OBJECT_TYPE_DEFINITION => function (ObjectTypeDefinitionNode $node, $key, $parent, $path, $ancestors) {

                    foreach($node->interfaces as $interface) {
                        if(in_array($interface->name->value, static::CONTENT_INTERFACES)) {
                            $node->interfaces[] = new InterfaceTypeDefinitionNode([
                                'name'        => new NameNode(['value' => static::FIELDABLE_INTERFACE]),
                            ]);
                            break;
                        }
                    }

                    return $node;
                },
            ]
        ]);
    }
}
