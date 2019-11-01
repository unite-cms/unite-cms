<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;
use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;
use UniteCMS\CoreBundle\Event\ContentEvent;

/**
 * @UniteAssert\ValidContent(groups={ "Default", ContentEvent::CREATE, ContentEvent::UPDATE, ContentEvent::DELETE, ContentEvent::REVERT, ContentEvent::RECOVER })
 */
interface ContentInterface
{
    public function getId() : ?string;

    public function getType() : string;

    /**
     * @return FieldData[]
     */
    public function getData() : array;

    public function getFieldData(string $fieldName) : ?FieldData;

    public function getDeleted() : ?DateTime;
}
