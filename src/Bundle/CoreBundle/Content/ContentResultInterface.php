<?php


namespace UniteCMS\CoreBundle\Content;

interface ContentResultInterface
{
    /**
     * @return int
     */
    public function getTotal() : int;

    /**
     * @return ContentInterface[]
     */
    public function getResult() : array;
}

