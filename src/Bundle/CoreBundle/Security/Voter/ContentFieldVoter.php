<?php


namespace UniteCMS\CoreBundle\Security\Voter;

use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentFieldVoter extends Voter
{
    const READ = 'read';
    const UPDATE = 'update';

    const PERMISSIONS = [
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
        return in_array($attribute, self::PERMISSIONS) && $subject instanceof FieldData;
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {

        if(!$subject instanceof FieldData) {
            return self::ACCESS_ABSTAIN;
        }

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        if(!$contentType = $contentTypeManager->getContentType($subject->getContentType())) {
            return self::ACCESS_ABSTAIN;
        }

        if(!$contentTypeField = $contentType->getField($subject->getId())) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->authorizationChecker->isGranted(
            $contentTypeField->getPermission($attribute),
            $subject
        );
    }
}