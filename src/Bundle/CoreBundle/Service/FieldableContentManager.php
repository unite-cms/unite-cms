<?php


namespace UniteCMS\CoreBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentLogEntry;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SoftDeleteableFieldableContent;
use UniteCMS\CoreBundle\Exception\NotValidException;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;

class FieldableContentManager
{
    const PERMISSION_LIST = 'list';
    const PERMISSION_CREATE = 'create';
    const PERMISSION_VIEW = 'view';
    const PERMISSION_UPDATE = 'update';
    const PERMISSION_DELETE = 'delete';

    /**
     * @var EntityManagerInterface $entityManager
     */
    protected $entityManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
    }

    /**
     * Checks permission of the current user for Fieldable or FieldableContent objects and one permission.
     * @param Fieldable|FieldableContent $content
     * @param string $attribute
     * @return bool
     */
    public function isGranted($content, string $attribute) : bool {
        if(!$content instanceof Fieldable && !$content instanceof FieldableContent) {
            throw new InvalidArgumentException(sprintf('Content must be one of "%s".', join(', ', [Fieldable::class, FieldableContent::class])));
        }

        $actual_attribute = null;

        if($content instanceof ContentType || $content instanceof Content) {
            switch ($attribute) {
                case self::PERMISSION_LIST : $actual_attribute = ContentVoter::LIST; break;
                case self::PERMISSION_CREATE : $actual_attribute = ContentVoter::CREATE; break;
                case self::PERMISSION_VIEW : $actual_attribute = ContentVoter::VIEW; break;
                case self::PERMISSION_UPDATE : $actual_attribute = ContentVoter::UPDATE; break;
                case self::PERMISSION_DELETE : $actual_attribute = ContentVoter::DELETE; break;
                default: $attribute = null;
            }
        }

        if($content instanceof SettingType || $content instanceof Setting) {
            switch ($attribute) {
                case self::PERMISSION_VIEW : $actual_attribute = SettingVoter::VIEW; break;
                case self::PERMISSION_UPDATE: $actual_attribute = SettingVoter::UPDATE; break;
                default: $attribute = null;
            }
        }

        if($content instanceof DomainMemberType || $content instanceof DomainMember) {
            switch ($attribute) {
                case self::PERMISSION_LIST : $actual_attribute = DomainMemberVoter::LIST; break;
                case self::PERMISSION_CREATE : $actual_attribute = DomainMemberVoter::CREATE; break;
                case self::PERMISSION_VIEW : $actual_attribute = DomainMemberVoter::VIEW; break;
                case self::PERMISSION_UPDATE : $actual_attribute = DomainMemberVoter::UPDATE; break;
                case self::PERMISSION_DELETE : $actual_attribute = DomainMemberVoter::DELETE; break;
                default: $attribute = null;
            }
        }

        if(empty($actual_attribute)) {
            throw new InvalidArgumentException(sprintf('Attribute 2%s" not supported for object of type "%s".', $attribute, get_class($content)));
        }

        return $this->authorizationChecker->isGranted($actual_attribute, $content);
    }

    /**
     * Finds a content object by it's fieldable and id. Optional also searches for soft_deleted content.
     * @param Fieldable $fieldable
     * @param string $contentId
     * @param bool $include_soft_deleted
     * @return FieldableContent
     */
    public function find(Fieldable $fieldable, string $contentId, bool $include_soft_deleted = false) : ?FieldableContent {

        $contentClass = null;
        $contentTypeName = null;
        $voterAttribute = null;

        if($fieldable instanceof ContentType) {
            $contentClass = Content::class;
            $contentTypeName = 'contentType';
        }

        else if($fieldable instanceof DomainMemberType) {
            $contentClass = DomainMember::class;
            $contentTypeName = 'memberType';
        }

        else if($fieldable instanceof SettingType) {
            $contentClass = Setting::class;
            $contentTypeName = 'settingType';
        }

        if(empty($contentClass) || empty($contentTypeName)) {
            throw new InvalidArgumentException(sprintf('Fieldable must be one of "%s".', join(', ', [ContentType::class, DomainMemberType::class, SettingType::class])));
        }
        $disable_softdelete_filter = $include_soft_deleted && is_subclass_of($contentClass, SoftDeleteableFieldableContent::class);

        if($disable_softdelete_filter) { $this->entityManager->getFilters()->disable('gedmo_softdeleteable'); }

        /**
         * @var FieldableContent $content
         */
        $content = $this->entityManager->getRepository($contentClass)->findOneBy(
            [
                'id' => $contentId,
                $contentTypeName => $fieldable,
            ]
        );

        if($disable_softdelete_filter) { $this->entityManager->getFilters()->enable('gedmo_softdeleteable'); }
        return $content;
    }

    /**
     * Mark softdeleteable fieldable content as deleted or permanently delete fieldable content.
     *
     * @param FieldableContent $content
     * @param bool $persist
     * @return FieldableContent
     */
    public function delete(FieldableContent $content, bool $persist = false) : FieldableContent {
        $violations = $this->validator->validate($content, null, ['DELETE']);
        if (count($violations) > 0) {
            throw new NotValidException($violations);
        }

        if($persist) {
            $this->entityManager->remove($content);
            $this->entityManager->flush($content);
        }

        return $content;
    }

    /**
     * Completely delete content from database.
     *
     * @param SoftDeleteableFieldableContent $content
     * @param bool $persist
     * @return SoftDeleteableFieldableContent
     */
    public function deleteDefinitely(SoftDeleteableFieldableContent $content, bool $persist = false) : SoftDeleteableFieldableContent {

        if($content->getDeleted() == null) {
            throw new InvalidArgumentException(sprintf('You can only definitely deleted already deleted content objects.'));
        }
        return $this->delete($content, $persist);
    }

    /**
     * Recover deleted content.
     *
     * @param SoftDeleteableFieldableContent $content
     * @param bool $persist
     * @return SoftDeleteableFieldableContent
     */
    public function recover(SoftDeleteableFieldableContent $content, bool $persist = false) : SoftDeleteableFieldableContent {

        if(!$content->getDeleted()) {
            throw new InvalidArgumentException('You can only recover deleted content objects.');
        }
        if($persist) {
            $content->recoverDeleted();
            $this->entityManager->flush($content);
        }

        return $content;
    }

    /**
     * Get all revisions of a fieldable content.
     *
     * @param FieldableContent $content
     * @return ContentLogEntry[]
     */
    public function getRevisions(FieldableContent $content) : array {
        return $this->entityManager
            ->getRepository(ContentLogEntry::class)
            ->getLogEntries($content);
    }

    /**
     * Revert content to given version.
     *
     * @param FieldableContent $content
     * @param int $version
     * @param bool $persist
     * @return FieldableContent
     */
    public function revert(FieldableContent $content, int $version, bool $persist = false) : FieldableContent {
        if($persist) {
            $this->entityManager->getRepository(ContentLogEntry::class)->revert($content, $version);
            $this->entityManager->persist($content);
            $this->entityManager->flush($content);
        }
        return $content;
    }
}
