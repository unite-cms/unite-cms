<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Security\AccessExpressionChecker;

class ContentVoter extends Voter
{
    const LIST = 'list content';
    const CREATE = 'create content';
    const VIEW = 'view content';
    const UPDATE = 'update content';
    const DELETE = 'delete content';
    const TRANSLATE = 'translate content';

    const BUNDLE_PERMISSIONS = [self::LIST, self::CREATE];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE, self::DELETE, self::TRANSLATE];

    /**
     * @var AccessExpressionChecker $accessExpressionChecker
     */
    protected $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new AccessExpressionChecker();
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (in_array($attribute, self::BUNDLE_PERMISSIONS)) {
            return ($subject instanceof ContentType);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Content && $subject->getDeleted() == null);
        }

        return false;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if(!$subject instanceof Content && !$subject instanceof ContentType) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        $contentType = $subject instanceof ContentType ? $subject : $subject->getContentType();

        if(!$contentType) {
            return self::ACCESS_ABSTAIN;
        }

        $domainMembers = $token->getUser()->getDomainMembers($contentType->getDomain());

        // Only work for non-deleted content
        if ($subject instanceof Content && $subject->getDeleted() != null) {
            return self::ACCESS_ABSTAIN;
        }

        // If the requested permission is not defined, throw an exception.
        if (empty($contentType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in ContentType '$contentType'");
        }

        // If the expression evaluates to true, we grant access.
        foreach ($domainMembers as $domainMember) {
            if($this->accessExpressionChecker->evaluate($contentType->getPermissions()[$attribute], $domainMember, $subject instanceof Content ? $subject : null)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
