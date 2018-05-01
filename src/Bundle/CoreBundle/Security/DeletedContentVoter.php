<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;

class DeletedContentVoter extends Voter
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

        // This voter can decide on a Content subject for APIClients of the same domain.
        if ($token->getUser() instanceof DomainAccessor) {
            $roles = $token->getUser()->getDomainRoles($subject->getContentType()->getDomain());

            // Platform admins are allowed to preform all actions.
            if (in_array(User::ROLE_PLATFORM_ADMIN, $token->getUser()->getRoles())) {
                return self::ACCESS_GRANTED;
            }

            // All organization admins are allowed to preform all content actions.
            foreach ($token->getUser()->getOrganizations() as $organizationMember) {
                if (in_array(Organization::ROLE_ADMINISTRATOR, $organizationMember->getRoles())) {

                    if ($subject->getContentType()->getDomain()->getOrganization()->getId(
                        ) === $organizationMember->getOrganization()->getId()) {
                        return self::ACCESS_GRANTED;
                    }
                }
            }
        } else {
            return self::ACCESS_ABSTAIN;
        }

        // User can perform all actions on subject, if he_she can update it.
        $allowedRoles = $subject->getContentType()->getPermissions()[ContentVoter::UPDATE];

        foreach ($roles as $userRole) {
            $userRole = ($userRole instanceof Role) ? $userRole->getRole() : $userRole;
            if (in_array($userRole, $allowedRoles)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
