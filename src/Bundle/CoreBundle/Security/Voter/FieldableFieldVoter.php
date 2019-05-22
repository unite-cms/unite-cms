<?php

namespace UniteCMS\CoreBundle\Security\Voter;

use App\Bundle\CoreBundle\Model\FieldableFieldContent;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;

class FieldableFieldVoter extends Voter
{
    const LIST = 'list field';
    const VIEW = 'view field';
    const UPDATE = 'update field';

    const BUNDLE_PERMISSIONS = [self::LIST];
    const ENTITY_PERMISSIONS = [self::VIEW, self::UPDATE];

    /**
     * @var UniteExpressionChecker $accessExpressionChecker
     */
    protected $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new UniteExpressionChecker();
    }

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
        return
            in_array($attribute, self::ENTITY_PERMISSIONS) && $subject instanceof FieldableFieldContent
            || in_array($attribute, self::BUNDLE_PERMISSIONS) && $subject instanceof FieldableField;
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
        $content = null;
        $field = null;

        if($subject instanceof FieldableFieldContent) {
            $field = $subject->getField();
            $content = $subject->getContent();
        }

        if($subject instanceof FieldableField) {
            $field = $subject;
        }

        if(empty($field)) {
            return self::ACCESS_ABSTAIN;
        }

        // We can only vote on DomainAccessor user objects.
        if (!$token->getUser() instanceof DomainAccessor) {
            return self::ACCESS_ABSTAIN;
        }

        // If the requested permission is not defined, throw an exception.
        if (empty($field->getPermissions()[$attribute])) {
            throw new InvalidArgumentException("Permission '$attribute' was not found in FieldableField '{$field->getTitle()}'");
        }

        $domainMembers = $token->getUser()->getDomainMembers($field->getEntity()->getRootEntity()->getDomain());

        // If the expression evaluates to true, we grant access.
        foreach ($domainMembers as $domainMember) {

            $this->accessExpressionChecker
                ->clearVariables()
                ->registerDomainMember($domainMember);

            if(!empty($content)) {
                $this->accessExpressionChecker->registerFieldableContent($content);
            }

            if($this->accessExpressionChecker->evaluateToBool($field->getPermissions()[$attribute])) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
