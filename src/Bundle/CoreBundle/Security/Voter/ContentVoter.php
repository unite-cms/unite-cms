<?php


namespace UniteCMS\CoreBundle\Security\Voter;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Domain\DomainManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;

class ContentVoter extends Voter
{
    const QUERY = 'query';
    const MUTATION = 'mutation';
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const PERMANENT_DELETE = 'permanent_delete';

    const LIST_PERMISSIONS = [
        self::QUERY,
        self::MUTATION,
        self::CREATE,
    ];

    const ENTITY_PERMISSIONS = [
        self::READ,
        self::UPDATE,
        self::DELETE,
        self::PERMANENT_DELETE,
    ];

    const PERMISSIONS = [
        self::QUERY,
        self::MUTATION,
        self::CREATE,
        self::READ,
        self::UPDATE,
        self::DELETE,
        self::PERMANENT_DELETE,
    ];

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->domainManager = $domainManager;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, self::PERMISSIONS)
            && ($subject instanceof ContentInterface || $subject instanceof ContentType);
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {

        $type = null;
        $data = null;

        if($subject instanceof ContentInterface) {
            $type = $subject->getType();
            $data = $subject;
        }

        else if ($subject instanceof ContentType) {
            $type = $subject->getId();
        }

        if(empty($type)) {
            return self::ACCESS_ABSTAIN;
        }

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        if(!$contentType = $contentTypeManager->getAnyType($type)) {
            return self::ACCESS_ABSTAIN;
        }

        return (bool)$this->expressionLanguage->evaluate($contentType->getPermission($attribute), [
            'content' => $data,
        ]);
    }
}