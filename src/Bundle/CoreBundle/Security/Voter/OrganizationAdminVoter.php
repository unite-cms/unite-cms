<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 21.06.17
 * Time: 09:15
 */

namespace UniteCMS\CoreBundle\Security\Voter;

use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;

class OrganizationAdminVoter extends Voter
{
    const SUPPORTED_OBJECTS = [
        Organization::class,
        Domain::class,
        SettingType::class,
        ContentType::class,
        Setting::class,
        Content::class,
        DomainMemberType::class,
        DomainMember::class,
        FieldableField::class,
        FieldableFieldContent::class,
    ];

    /**
     * The organization admin voter supports all object subjects.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if(is_object($subject)) {
            $subject = get_class($subject);
        }

        if(!empty($subject)) {
            foreach(self::SUPPORTED_OBJECTS as $class) {
                if(is_a($subject, $class, true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The platform organization voter grants access to all subjects for the organization.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$token->getUser() instanceof User) {
            return self::ACCESS_ABSTAIN;
        }

        /**
         * @var User $user
         */
        $user = $token->getUser();

        // Find all organizations, this user is an admin for.
        $adminOrganizations = [];
        foreach($user->getOrganizations() as $organizationMember) {
            if(in_array(Organization::ROLE_ADMINISTRATOR, $organizationMember->getRoles())) {
                $adminOrganizations[] = $organizationMember->getOrganization();
            }
        }

        // If we the user is no organization admin at all, we can't continue.
        if(empty($adminOrganizations)) {
            return self::ACCESS_ABSTAIN;
        }

        // Some actions are allowed for organization admins, independently from the current organization. For example
        // an org admin can always create a new domain. In this case, we don't have an object to vote on.
        if(is_string($subject)) {

            if($subject === Domain::class && $attribute === DomainVoter::CREATE) {
                return self::ACCESS_GRANTED;
            }
        }

        // If we have an object to vote on, get the organization of this subject and vote on it.
        elseif(is_object($subject)) {

            // If we found a organization for this subject and it is one of the adminOrganizations for this user.
            $subjectOrganization = $this->findSubjectOrganization($subject);

            foreach($adminOrganizations as $adminOrganization) {
                if($adminOrganization->getId() === $subjectOrganization->getId()) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function findSubjectOrganization($subject) {

        if($subject instanceof Organization) {
            return $subject;
        }

        if($subject instanceof Domain) {
            return $subject->getOrganization();
        }

        if($subject instanceof SettingType) {
            return $subject->getDomain()->getOrganization();
        }

        if($subject instanceof ContentType) {
            return $subject->getDomain()->getOrganization();
        }

        if($subject instanceof Setting) {
            return $subject->getSettingType()->getDomain()->getOrganization();
        }

        if($subject instanceof Content) {
            return $subject->getContentType()->getDomain()->getOrganization();
        }

        if($subject instanceof DomainMemberType) {
            return $subject->getDomain()->getOrganization();
        }

        if($subject instanceof DomainMember) {
            return $subject->getDomainMemberType()->getDomain()->getOrganization();
        }

        if($subject instanceof FieldableField) {
            return $subject->getEntity()->getRootEntity()->getDomain()->getOrganization();
        }

        if($subject instanceof FieldableFieldContent) {
            return $subject->getField()->getEntity()->getRootEntity()->getDomain()->getOrganization();
        }

        return null;
    }
}
