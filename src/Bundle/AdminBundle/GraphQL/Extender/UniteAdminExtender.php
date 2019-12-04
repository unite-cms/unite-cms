<?php

namespace UniteCMS\AdminBundle\GraphQL\Extender;

use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Schema\Extender\SchemaExtenderInterface;

class UniteAdminExtender implements SchemaExtenderInterface
{
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema): string
    {
        $this->domainManager
            ->setGlobalParameter('UNITE_ADMIN_ACCESS_LOGS', $this->domainManager->getIsAdminExpression())
            ->setGlobalParameter('UNITE_ADMIN_ACCESS_SCHEMA_FILES', $this->domainManager->getIsAdminExpression())
            ->setGlobalParameter('UNITE_ADMIN_ACCESS_QUERY_EXPLORER', $this->domainManager->getIsAdminExpression())
            ->setGlobalParameter('UNITE_ADMIN_ACCESS_ADMIN_VIEWS', 'not user.isAnonymous()');
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/unite-admin-extender.graphql');
    }
}
