<?php


namespace UniteCMS\CoreBundle\Security\Voter;

use UniteCMS\CoreBundle\Content\ContentField;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentFieldVoter extends Voter
{
    const MUTATION = 'mutation';
    const READ = 'read';
    const UPDATE = 'update';

    const PERMISSIONS = [
        self::MUTATION,
        self::READ,
        self::UPDATE,
    ];

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
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, self::PERMISSIONS)
            && ($subject instanceof ContentField || $subject instanceof ContentTypeField);
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {

        $fieldType = null;
        $fieldData = null;

        if($subject instanceof ContentField) {
            $contentType = $this->domainManager->current()->getContentTypeManager()->getContentType($subject->getContent()->getType());
            $fieldType = $contentType->getField($subject->getFieldId());
            $fieldData = $subject->getContent();
        }

        else if ($subject instanceof ContentTypeField) {
            $fieldType = $subject;
        }

        if(empty($fieldType)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->authorizationChecker->isGranted($fieldType->getPermission($attribute), $fieldData);
    }
}
