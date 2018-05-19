<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Security\AccessExpressionChecker;

class SettingVoter extends Voter
{
    const VIEW = 'view setting';
    const UPDATE = 'update setting';

    const BUNDLE_PERMISSIONS = [];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE];

    /**
     * @var AccessExpressionChecker $accessExpressionChecker
     */
    private $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new AccessExpressionChecker();
    }

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
        if(!$subject instanceof Setting && !$subject instanceof SettingType) {
            return self::ACCESS_ABSTAIN;
        }

        // If the token is not an ApiClient it must be an User.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        $settingType = $subject instanceof Setting ? $subject->getSettingType() : $subject;

        if(!$settingType) {
            return self::ACCESS_ABSTAIN;
        }

        $domainMember = $token->getUser()->getDomainMember($settingType->getDomain());

        // We can only vote if this user is member of the subject's domain.
        if(!$domainMember) {
            return self::ACCESS_ABSTAIN;
        }

        // If the requested permission is not defined, throw an exception.
        if (empty($settingType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in SettingType '$settingType'");
        }

        // If the expression evaluates to true, we grant access.
        if($this->accessExpressionChecker->evaluate($settingType->getPermissions()[$attribute], $domainMember, $subject instanceof Setting ? $subject : $settingType->getSetting())) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
