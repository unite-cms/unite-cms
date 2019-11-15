<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\Util;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;

class HideDirectiveModifier implements SchemaModifierInterface
{
    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(SaveExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(DocumentNode &$document, Schema $schema) : void
    {
        $hideMap = [];

        foreach($schema->getTypeMap() as $type) {

            $hideMap[$type->name] = [
                'hide' => Util::isHidden($type->astNode, $this->expressionLanguage),
                'fields' => [],
            ];

            // Check @hide directive on fields.
            if($type instanceof ObjectType || $type instanceof InputObjectType) {
                foreach ($type->getFields() as $field) {
                    if(Util::isHidden($field->astNode, $this->expressionLanguage)) {
                        $hideMap[$type->name]['fields'][$field->name] = true;
                    }
                }
            }
        }

        // Modify the schema document and remove all hidden objects and fields.
        $document = Visitor::visit($document, [
            'enter' => [
                NodeKind::OBJECT_TYPE_DEFINITION => function ($node, $key, $parent, $path, $ancestors) use ($hideMap) {

                    // Remove hidden object types.
                    if($hideMap[$node->name->value]['hide']) {
                        return Visitor::removeNode();
                    }

                    // Remove hidden fields.
                    if(isset($hideMap[$node->name->value])) {
                        if (!empty($hideMap[$node->name->value]['fields']) && isset($node->fields)) {
                            foreach ($node->fields as $f_key => $field) {
                                if(!empty($hideMap[$node->name->value]['fields'][$field->name->value])) {
                                    $node->fields[$f_key]->mark_to_remove = true;
                                }
                            }
                        }
                    }

                    return $node;
                },

                NodeKind::FIELD_DEFINITION => function ($node, $key, $parent, $path, $ancestors) use ($hideMap) {
                    if(!empty($node->mark_to_remove)) {
                        return Visitor::removeNode();
                    }

                    return $node;
                }
            ]
        ]);
    }
}
