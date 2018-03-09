<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 21.06.17
 * Time: 09:15
 */

namespace UnitedCMS\CoreBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;

class OrganizationVoter extends Voter
{
    const LIST = 'list organization';
    const CREATE = 'create organization';
    const VIEW = 'view organization';
    const UPDATE = 'update organization';
    const DELETE = 'delete organization';

    const BUNDLE_PERMISSIONS = [self::LIST, self::CREATE];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE, self::DELETE];

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
            return (is_string($subject) && $subject === Organization::class);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Organization);
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
        // This voter can only decide for united users, because the decision is based on domain roles.
        if (!$token->getUser() instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        // Platform admins are allowed to preform all actions.
        if (in_array(User::ROLE_PLATFORM_ADMIN, $token->getUser()->getRoles())) {
            return self::ACCESS_GRANTED;
        }

        // All users are allowed to list organizations.
        if ($attribute === self::LIST && in_array(User::ROLE_USER, $token->getUser()->getRoles())) {
            return self::ACCESS_GRANTED;
        }

        if ($subject instanceof Organization) {
            foreach ($token->getUser()->getOrganizations() as $organizationMember) {
                if ($organizationMember->getOrganization()->getId() === $subject->getId()) {
                    return $this->checkPermission($attribute, $organizationMember);
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * Check if the user has access to the organization.
     *
     * @param $attribute
     * @param OrganizationMember $organizationMember
     * @return bool
     */
    private function checkPermission($attribute, OrganizationMember $organizationMember)
    {

        // Admins can perform all organization entity actions.
        if (in_array(Organization::ROLE_ADMINISTRATOR, $organizationMember->getRoles())) {
            return in_array($attribute, self::ENTITY_PERMISSIONS);
        }

        // Users can only view the organization
        if (in_array(Organization::ROLE_USER, $organizationMember->getRoles())) {
            return $attribute == self::VIEW;
        }

        return self::ACCESS_DENIED;
    }
}