<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;

class ContentVoter extends Voter
{
    const LIST = 'list content';
    const CREATE = 'create content';
    const VIEW = 'view content';
    const UPDATE = 'update content';
    const DELETE = 'delete content';

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
            return ($subject instanceof ContentType);
        }

        if (in_array($attribute, self::ENTITY_PERMISSIONS)) {
            return ($subject instanceof Content && $subject->getDeleted() == null);
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
        // Only work for non-deleted content
        if ($subject instanceof Content && $subject->getDeleted() != null) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        // Check bundle and entity actions on ContentType or Content objects.
        if ($subject instanceof ContentType) {
            return $this->checkPermission(
                $attribute,
                $subject,
                $token->getUser()->getDomainRoles($subject->getDomain())
            );
        }
        if ($subject instanceof Content) {
            return $this->checkPermission(
                $attribute,
                $subject->getContentType(),
                $token->getUser()->getDomainRoles($subject->getContentType()->getDomain())
            );
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * Check if the user has the role for the contentType.
     *
     * @param $attribute
     * @param ContentType $contentType
     * @param array $roles
     * @return bool
     */
    protected function checkPermission($attribute, ContentType $contentType, array $roles)
    {

        if (empty($contentType->getPermissions()[$attribute])) {
            throw new \InvalidArgumentException("Permission '$attribute' was not found in ContentType '$contentType'");
        }

        $allowedRoles = $contentType->getPermissions()[$attribute];

        foreach ($roles as $userRole) {
            $userRole = ($userRole instanceof Role) ? $userRole->getRole() : $userRole;
            if (in_array($userRole, $allowedRoles)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
