<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use GraphQL\Type\Schema;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class QueryExtender implements SchemaExtenderInterface
{
    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema): string
    {
        $extension = '';

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        foreach($contentTypeManager->getContentTypes() as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {
                if($this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
                    $extension .= sprintf('
                        get%1$s(id: ID!) : %1$s
                        find%1$s(filter: UniteFilterInput, orderBy: [UniteOrderByInput!] = { field: "updated", desc: true }, limit: Int = 20, offset: Int = 0, includeDeleted: Boolean = false) : %1$sResult!
                    ', $type->getId());
                }
            }
        }

        foreach($contentTypeManager->getSingleContentTypes() as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {
                if($this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
                    $extension .= sprintf('
                        get%1$s : %1$s
                    ', $type->getId());
                }
            }
        }

        if(!empty($extension)) {
            $extension = sprintf('extend type Query {
                %s
            }', $extension);
        }

        return $extension;
    }
}
