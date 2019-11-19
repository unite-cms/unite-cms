<?php


namespace UniteCMS\CoreBundle\Log;


use DateTime;

interface LogInterface
{

    /**
     * @return string
     */
    public function getLevel() : string;

    /**
     * @return string
     */
    public function getMessage() : string;

    /**
     * @return string|null
     */
    public function getUsername() : ?string;

    /**
     * @return DateTime
     */
    public function getCreated() : DateTime;
}
