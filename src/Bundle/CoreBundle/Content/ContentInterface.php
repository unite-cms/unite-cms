<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;
use Doctrine\Common\Collections\Collection;
use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;
use UniteCMS\CoreBundle\Event\ContentEvent;

/**
 * @UniteAssert\ValidContent
 * @UniteAssert\ValidContent(groups={ ContentEvent::CREATE, ContentEvent::UPDATE, ContentEvent::DELETE, ContentEvent::REVERT, ContentEvent::RECOVER, ContentEvent::PERMANENT_DELETE })
 */
interface ContentInterface
{
    /**
     * @return bool
     */
    public function isNew() : bool;

    /**
     * @return string|null
     */
    public function getId() : ?string;

    /**
     * @return string
     */
    public function getType() : string;

    /**
     * @return FieldData[]
     */
    public function getData() : array;

    /**
     * @param string $fieldName
     *
     * @return FieldData|null
     */
    public function getFieldData(string $fieldName) : ?FieldData;

    /**
     * @return DateTime
     */
    public function getCreated() : DateTime;

    /**
     * @return DateTime
     */
    public function getUpdated() : DateTime;

    /**
     * @return DateTime|null
     */
    public function getDeleted() : ?DateTime;

    /**
     * @return string|null
     */
    public function getLocale() : ?string;

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale = null) : void;

    /**
     * @param ContentInterface $translate
     */
    public function setTranslate(?ContentInterface $translate = null) : void;

    /**
     * @return ContentInterface[]|Collection
     */
    public function getTranslations() : Collection;
}
