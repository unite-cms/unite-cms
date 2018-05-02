<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 21.06.17
 * Time: 09:15
 */

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;

class DomainVoter extends Voter
{
    const LIST = 'list domain';
    const CREATE = 'create domain';
    const VIEW = 'view domain';
    const UPDATE = 'update domain';
    const DELETE = 'delete domain';

    const BUNDLE_PERMISSIONS = [self::LIST];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE];

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * NOTE: create domain is not supported by this voter. The OrganizationAdmin and PlatformAdminVoter will vote on
     * "create domain" attributes.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (in_array($attribute, self::BUNDLE_PERMISSIONS)) {
            return (is_string($subject) && $subject === Domain::class);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Domain);
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
        // This voter can decide all permissions for DomainAccessors
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        /**
         * @var DomainAccessor $user
         */
        $user = $token->getUser();

        // Accessors can list domains.
        if ($attribute === self::LIST) {
            return self::ACCESS_GRANTED;
        }

        if($subject instanceof Domain) {

            // Accessors can view domain, if they are a member.
            if($attribute === self::VIEW) {
                if(!empty($user->getDomainRoles($subject))) {
                    return self::ACCESS_GRANTED;
                }
            }

            if($attribute === self::UPDATE) {
                if(in_array(Domain::ROLE_ADMINISTRATOR, $user->getDomainRoles($subject))) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
