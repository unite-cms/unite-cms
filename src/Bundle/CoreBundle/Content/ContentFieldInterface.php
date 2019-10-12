<?php


namespace UniteCMS\CoreBundle\Content;


interface ContentFieldInterface
{
    /**
     * @return string
     */
    public function __toString() : string;

    /**
     * @return string
     */
    public function getId() : string;

    /**
     * @return string
     */
    public function getType() : string;

    /**
     * @return string
     */
    public function getContentType() : string;
}
