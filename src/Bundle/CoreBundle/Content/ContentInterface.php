<?php


namespace UniteCMS\CoreBundle\Content;


interface ContentInterface
{
    public function getId() : ?string;

    public function getType() : string;

    /**
     * @return FieldData[]
     */
    public function getData() : array;

    public function getFieldData(string $fieldName) : ?FieldData;
}
