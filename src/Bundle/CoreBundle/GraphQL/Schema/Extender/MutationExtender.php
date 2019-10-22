<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use GraphQL\Type\Schema;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class MutationExtender implements SchemaExtenderInterface
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

        foreach(($contentTypeManager->getContentTypes() + $contentTypeManager->getUserTypes()) as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {
                if($this->authorizationChecker->isGranted(ContentVoter::MUTATION, $type)) {


                    // Only add data attribute if we have real fields
                    if(count($type->getFields()) > 0) {
                        $extension .= sprintf('
                            create%1$s(data: %1$sInput!, persist: Boolean!) : %1$s
                            update%1$s(id: ID!, data: %1$sInput!, persist: Boolean!) : %1$s
                            delete%1$s(id: ID!, persist: Boolean!) : %1$s
                            recover%1$s(id: ID!, persist: Boolean!) : %1$s
                        ', $type->getId());
                    }

                    else {
                        $extension .= sprintf('
                            create%1$s(persist: Boolean!) : %1$s
                            delete%1$s(persist: Boolean!) : %1$s
                        ', $type->getId());
                    }
                }
            }
        }

        foreach($contentTypeManager->getSingleContentTypes() as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {
                if($this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {

                    // Only add statements if we have real fields
                    if(count($type->getFields()) > 0) {
                        $extension .= sprintf('
                            update%1$s(data: %1$sInput!, persist: Boolean!) : %1$s
                        ', $type->getId());
                    }
                }
            }
        }

        if(!empty($extension)) {
            $extension = sprintf('extend type Mutation {
                %s
            }', $extension);
        }

        return $extension;
    }
}
