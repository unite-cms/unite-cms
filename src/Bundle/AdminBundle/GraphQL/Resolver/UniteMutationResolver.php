<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\AdminBundle\EditableSchemaFiles\EditableSchemaFileManager;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteMutationResolver implements FieldResolverInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var EditableSchemaFileManager $editableSchemaFileManager
     */
    protected $editableSchemaFileManager;

    public function __construct(DomainManager $domainManager, EditableSchemaFileManager $editableSchemaFileManager)
    {
        $this->domainManager = $domainManager;
        $this->editableSchemaFileManager = $editableSchemaFileManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteMutation';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        $domain = $this->domainManager->current();

        switch ($info->fieldName) {
            case 'updateSchemaFiles':
                return $this->editableSchemaFileManager->updateEditableSchemaFiles($domain, $args['schemaFiles'], $args['persist']);
            default: return null;
        }
    }
}
