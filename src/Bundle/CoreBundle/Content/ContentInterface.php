<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;
use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;
use UniteCMS\CoreBundle\Event\ContentEvent;

/**
 * @UniteAssert\ValidContent
 * @UniteAssert\ValidContent(groups={ ContentEvent::CREATE, ContentEvent::UPDATE, ContentEvent::DELETE, ContentEvent::REVERT, ContentEvent::RECOVER })
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
     * @return DateTime|null
     */
    public function getDeleted() : ?DateTime;
}
