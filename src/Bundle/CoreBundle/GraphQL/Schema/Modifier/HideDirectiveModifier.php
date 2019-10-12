<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Modifier;

use UniteCMS\CoreBundle\GraphQL\Util;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HideDirectiveModifier implements SchemaModifierInterface
{

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(DocumentNode &$document, Schema $schema) : void
    {
        $hideMap = [];

        foreach($schema->getTypeMap() as $type) {

            $hideMap[$type->name] = [
                'hide' => Util::isHidden($type->astNode, $this->authorizationChecker),
                'fields' => [],
            ];

            // Check @hide directive on fields.
            if($type instanceof ObjectType || $type instanceof InputObjectType) {
                foreach ($type->getFields() as $field) {
                    if(Util::isHidden($field->astNode, $this->authorizationChecker)) {
                        $hideMap[$type->name]['fields'][$field->name] = true;
                    }
                }
            }
        }

        // Modify the schema document and remove all hidden objects and fields.
        foreach($document->definitions as $d_key => $definition) {

            if(isset($hideMap[$definition->name->value])) {
                if ($hideMap[$definition->name->value]['hide']) {
                    unset($document->definitions[$d_key]);
                }

                if (!empty($hideMap[$definition->name->value]['fields']) && isset($definition->fields)) {
                    foreach ($definition->fields as $f_key => $field) {
                        if(!empty($hideMap[$definition->name->value]['fields'][$field->name->value])) {
                            unset($document->definitions[$d_key]->fields[$f_key]);
                        }
                    }
                }
            }
        }
    }
}
