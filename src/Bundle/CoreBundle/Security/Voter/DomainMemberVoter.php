<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;

class DomainMemberVoter extends Voter
{
    const LIST = 'list member';
    const CREATE = 'create member';
    const VIEW = 'view member';
    const UPDATE = 'update member';
    const DELETE = 'delete member';

    const BUNDLE_PERMISSIONS = [self::LIST, self::CREATE];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE, self::DELETE];

    /**
     * @var UniteExpressionChecker $accessExpressionChecker
     */
    protected $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new UniteExpressionChecker();
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
            return ($subject instanceof DomainMemberType);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof DomainMember);
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
        if(!$subject instanceof DomainMember && !$subject instanceof DomainMemberType) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        $domainMemberType = $subject instanceof DomainMemberType ? $subject : $subject->getDomainMemberType();

        if(!$domainMemberType) {
            return self::ACCESS_ABSTAIN;
        }

        $domainMembers = $token->getUser()->getDomainMembers($domainMemberType->getDomain());

        // If the requested permission is not defined, throw an exception.
        if (empty($domainMemberType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in DomainMemberType '$domainMemberType'");
        }

        // If the expression evaluates to true, we grant access.
        foreach ($domainMembers as $domainMember) {

            $this->accessExpressionChecker
                ->clearVariables()
                ->registerDomainMember($domainMember)
                ->registerFieldableContent($subject instanceof DomainMember ? $subject : null);

            if($this->accessExpressionChecker->evaluateToBool($domainMemberType->getPermissions()[$attribute])) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
