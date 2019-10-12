<?php


namespace UniteCMS\CoreBundle\Content;


interface ContentInterface
{
    public function getId() : ?string;

    public function getType() : string;

    /**
     * @return ContentFieldInterface[]
     */
    public function getData() : array;

    public function getFieldData(string $fieldName) : ?ContentFieldInterface;
}
