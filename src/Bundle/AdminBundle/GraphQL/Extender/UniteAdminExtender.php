<?php

namespace UniteCMS\AdminBundle\GraphQL\Extender;

use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;
use UniteCMS\CoreBundle\GraphQL\Schema\Extender\SchemaExtenderInterface;

class UniteAdminExtender implements SchemaExtenderInterface
{
    protected $domainManager;
    protected $permissions = [];

    public function __construct(
        DomainManager $domainManager,
        string $accessAdminViewsExpression = 'false',
        string $accessLogsExpression = null,
        string $accessSchemaFiles = null,
        string $accessQueryExplorer = null
    ) {
        $this->domainManager = $domainManager;
        $this->permissions = [
            'UNITE_ADMIN_ACCESS_ADMIN_VIEWS' => $accessAdminViewsExpression,
            'UNITE_ADMIN_ACCESS_LOGS' => $accessLogsExpression ?? $domainManager->getIsAdminExpression(),
            'UNITE_ADMIN_ACCESS_SCHEMA_FILES' => $accessSchemaFiles ?? $domainManager->getIsAdminExpression(),
            'UNITE_ADMIN_ACCESS_QUERY_EXPLORER' => $accessQueryExplorer ?? $domainManager->getIsAdminExpression(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema, ExecutionContext $context): string
    {
        foreach($this->permissions as $key => $expression) {
            $this->domainManager->setGlobalParameter($key, $expression);
        }

        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/unite-admin-extender.graphql');
    }
}
