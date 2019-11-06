<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
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
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker, SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->expressionLanguage = $expressionLanguage;
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
        foreach(($contentTypeManager->getContentTypes() + $contentTypeManager->getUserTypes()) as $type) {

            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->expressionLanguage)) {

                if($this->authorizationChecker->isGranted(ContentVoter::MUTATION, $type)) {
                    $extension .= $type->printInputType($this->fieldTypeManager, $this->authorizationChecker);
                }

                if($this->authorizationChecker->isGranted(ContentVoter::QUERY, $type)) {
                    $extension .= $type->printResultType();
                }
            }
        }

        // Generate input types for all embedded content types.
        foreach(($contentTypeManager->getSingleContentTypes() + $contentTypeManager->getEmbeddedContentTypes()) as $type) {
            if(!Util::isHidden($schema->getType($type->getId())->astNode, $this->expressionLanguage)) {
                $extension .= $type->printInputType($this->fieldTypeManager, $this->authorizationChecker);
            }
        }

        // Generate input types for all union content types.
        foreach($contentTypeManager->getUnionContentTypes() as $type) {
            $extension .= $type->printInputType($this->fieldTypeManager, $this->authorizationChecker);
        }

        return $extension;
    }
}
