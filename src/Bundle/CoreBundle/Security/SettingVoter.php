<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;

class SettingVoter extends Voter
{
    const VIEW = 'view setting';
    const UPDATE = 'update setting';

    const BUNDLE_PERMISSIONS = [];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE];


    /**
     * Determines if the attribute and subject are supported by this voter.
     * The setting voter can check entity permissions for setting as well as settingType
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Setting || $subject instanceof SettingType);
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
        // We can also vote on SettingTypes since there is exactly one setting per settingType.
        if ($subject instanceof SettingType) {
            $subject = $subject->getSetting();
        }

        // This voter can decide on a Content subject for APIClients.
        if ($token->getUser() instanceof ApiClient && ($subject instanceof Setting)) {

            if ($subject->getSettingType()->getDomain() !== $token->getUser()->getDomain()) {
                return self::ACCESS_ABSTAIN;
            }

            return $this->checkPermission($attribute, $subject->getSettingType(), $token->getRoles());
        }

        // If the token is not an ApiClient it must be an User.
        if (!$token->getUser() instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        // Platform admins are allowed to preform all actions.
        if (in_array(User::ROLE_PLATFORM_ADMIN, $token->getUser()->getRoles())) {
            return self::ACCESS_GRANTED;
        }

        // All organization admins are allowed to preform all setting actions.
        foreach ($token->getUser()->getOrganizations() as $organizationMember) {
            if (in_array(Organization::ROLE_ADMINISTRATOR, $organizationMember->getRoles())) {

                if ($subject instanceof Setting && $subject->getSettingType()->getDomain()->getOrganization()->getId() === $organizationMember->getOrganization()->getId()) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        // Check entity actions on Setting objects.
        if ($subject instanceof Setting) {
            return $this->checkPermission($attribute, $subject->getSettingType(), $token->getUser()->getDomainRoles($subject->getSettingType()->getDomain()));
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * Check if the user has the role for the settingType.
     *
     * @param $attribute
     * @param SettingType $settingType
     * @param array $roles
     * @return bool
     */
    private function checkPermission($attribute, SettingType $settingType, array $roles)
    {

        if (empty($settingType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in SettingType '$settingType'");
        }

        $allowedRoles = $settingType->getPermissions()[$attribute];

        foreach ($roles as $userRole) {
            $userRole = ($userRole instanceof Role) ? $userRole->getRole() : $userRole;
            if (in_array($userRole, $allowedRoles)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
