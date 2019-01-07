<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\DomainAccessor;

class DeletedContentVoter extends ContentVoter
{
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
        if (in_array($attribute, ContentVoter::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Content && $subject->getDeleted() != null);
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
        if(!$subject instanceof Content) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        $contentType = $subject->getContentType();

        if(!$contentType) {
            return self::ACCESS_ABSTAIN;
        }

        $domainMembers = $token->getUser()->getDomainMembers($contentType->getDomain());

        // Only work for non-deleted content
        if ($subject->getDeleted() == null) {
            return self::ACCESS_ABSTAIN;
        }

        // If the requested permission is not defined, throw an exception.
        if (empty($contentType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in ContentType '$contentType'");
        }

        // in order to perform RUD actions on deleted content the user must have update permissions.
        if(in_array($attribute, self::ENTITY_PERMISSIONS)) {
            $attribute = ContentVoter::UPDATE;
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
