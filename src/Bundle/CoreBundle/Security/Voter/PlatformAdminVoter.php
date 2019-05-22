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

class PlatformAdminVoter extends Voter
{
    const SUPPORTED_OBJECTS = [
        Organization::class,
        Domain::class,
        DomainMemberType::class,
        SettingType::class,
        ContentType::class,
        Setting::class,
        Content::class,
        DomainMember::class,
        FieldableFieldContent::class,
        FieldableField::class,
    ];

    /**
     * The platform admin voter supports all subjects.
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
     * The platform admin voter grants access to all subjects and all attributes for platform admins.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($token->getUser() instanceof User && in_array(User::ROLE_PLATFORM_ADMIN, $token->getUser()->getRoles())) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
