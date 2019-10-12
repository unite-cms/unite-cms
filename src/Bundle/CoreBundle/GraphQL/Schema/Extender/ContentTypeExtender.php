<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use GraphQL\Type\Schema;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class ContentTypeExtender implements SchemaExtenderInterface
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
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

        // Generate input types for all content types.
        foreach($contentTypeManager->getContentTypes() as $type) {

            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {

                if($this->authorizationChecker->isGranted(ContentVoter::MUTATION, $type)) {
                    $extension .= $type->printInputType($this->fieldTypeManager);
                }

                if($this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
                    $extension .= $type->printResultType();
                }
            }
        }

        // Generate input types for all embedded content types.
        foreach($contentTypeManager->getEmbeddedContentTypes() as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->authorizationChecker)) {
                $extension .= $type->printInputType($this->fieldTypeManager);
            }
        }

        return $extension;
    }
}
