<?php


namespace UniteCMS\CoreBundle\Content;

interface ContentResultInterface
{
    /**
     * @return string
     */
    public function getType() : string;

    /**
     * @return int
     */
    public function getTotal() : int;

    /**
     * @return ContentInterface[]
     */
    public function getResult() : array;
}

