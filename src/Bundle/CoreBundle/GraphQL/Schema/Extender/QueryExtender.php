<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;
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
     * @var  SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->expressionLanguage = $expressionLanguage;
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema, ExecutionContext $context): string
    {
        $extension = '';

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        foreach(($contentTypeManager->getContentTypes() + $contentTypeManager->getUserTypes()) as $type) {
            if($context->isBypassAccessCheck() || !Util::isHidden($schema->getType($type->getId())->astNode, $this->expressionLanguage)) {
                if($context->isBypassAccessCheck() || $this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
                    $extension .= sprintf('
                        get%1$s(id: ID!, includeDeleted: Boolean = false) : %1$s
                        find%1$s(filter: UniteFilterInput, orderBy: [UniteOrderByInput!], limit: Int = 20, offset: Int = 0, includeDeleted: Boolean = false) : %1$sResult!
                    ', $type->getId());
                }
            }
        }

        foreach($contentTypeManager->getSingleContentTypes() as $type) {
            if($context->isBypassAccessCheck() || !Util::isHidden($schema->getType($type->getId())->astNode, $this->expressionLanguage)) {
                if($context->isBypassAccessCheck() || $this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
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

        if(!empty($contentTypeManager->getUserTypes())) {
            $extension .= 'extend type UniteQuery {
                me: UniteUser @hide(if: "user.isAnonymous()")
            }';
        }

        return $extension;
    }
}
