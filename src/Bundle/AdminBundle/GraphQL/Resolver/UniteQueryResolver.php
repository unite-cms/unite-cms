<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\AdminBundle\AdminView\AdminViewManager;
use UniteCMS\AdminBundle\EditableSchemaFiles\EditableSchemaFileManager;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteQueryResolver implements FieldResolverInterface
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var AdminViewManager $adminViewManager
     */
    protected $adminViewManager;

    /**
     * @var EditableSchemaFileManager
     */
    protected $editableSchemaFileManager;

    public function __construct(DomainManager $domainManager, AdminViewManager $adminViewManager, EditableSchemaFileManager $editableSchemaFileManager)
    {
        $this->domainManager = $domainManager;
        $this->adminViewManager = $adminViewManager;
        $this->editableSchemaFileManager = $editableSchemaFileManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteQuery';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        $domain = $this->domainManager->current();

        switch ($info->fieldName) {
            case 'logs':
                return $domain->getLogger()->getLogs($domain, $args['before'], $args['after'] ?? null);

            case 'adminViews':
                return $this->adminViewManager->getAdminViews($domain);

            case 'schemaFiles':
                return $this->editableSchemaFileManager->getEditableSchemaFiles($domain);

            default: return null;
        }
    }
}
