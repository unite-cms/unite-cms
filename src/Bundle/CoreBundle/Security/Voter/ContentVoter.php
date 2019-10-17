<?php


namespace UniteCMS\CoreBundle\Security\Voter;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContentVoter extends Voter
{
    const QUERY = 'query';
    const MUTATION = 'mutation';
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    const PERMISSIONS = [
        self::QUERY,
        self::MUTATION,
        self::CREATE,
        self::READ,
        self::UPDATE,
        self::DELETE,
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
            && ($subject instanceof ContentInterface || $subject instanceof ContentType);
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {

        $type = null;

        if($subject instanceof ContentInterface) {
            $type = $subject->getType();
        }

        else if ($subject instanceof ContentType) {
            $type = $subject->getId();
        }

        if(empty($type)) {
            return self::ACCESS_ABSTAIN;
        }

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        if(!$contentType = $contentTypeManager->getAnyType($type)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->authorizationChecker->isGranted(
            $contentType->getPermission($attribute),
            $subject
        );
    }
}