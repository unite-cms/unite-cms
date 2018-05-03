<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;

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
        if (!$subject instanceof Content) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        // Accessors can access deleted content if and only if they are allowed to update it.
        if(in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return $this->checkPermission(
                self::UPDATE,
                $subject->getContentType(),
                $token->getUser()->getDomainRoles($subject->getContentType()->getDomain())
            );
        }

        return self::ACCESS_ABSTAIN;
    }
}
