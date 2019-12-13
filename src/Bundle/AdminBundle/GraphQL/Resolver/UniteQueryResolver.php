<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\AdminBundle\AdminView\AdminViewTypeManager;
use UniteCMS\AdminBundle\EditableSchemaFiles\EditableSchemaFileManager;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;

class UniteQueryResolver implements FieldResolverInterface
{
    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var AdminViewTypeManager $adminViewManager
     */
    protected $adminViewManager;

    /**
     * @var EditableSchemaFileManager
     */
    protected $editableSchemaFileManager;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager, AdminViewTypeManager $adminViewManager, EditableSchemaFileManager $editableSchemaFileManager)
    {
        $this->expressionLanguage = $expressionLanguage;
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

            case 'adminPermissions':
                return [
                    'LOGS' => (bool)$this->expressionLanguage->evaluate($this->domainManager->getGlobalParameters()['UNITE_ADMIN_ACCESS_LOGS']),
                    'SCHEMA' => (bool)$this->expressionLanguage->evaluate($this->domainManager->getGlobalParameters()['UNITE_ADMIN_ACCESS_SCHEMA_FILES']),
                    'QUERY_EXPLORER' => (bool)$this->expressionLanguage->evaluate($this->domainManager->getGlobalParameters()['UNITE_ADMIN_ACCESS_QUERY_EXPLORER']),
                ];

            case 'schemaFiles':
                return $this->editableSchemaFileManager->getEditableSchemaFiles($domain);

            default: return null;
        }
    }
}
