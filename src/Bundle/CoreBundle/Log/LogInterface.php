<?php


namespace UniteCMS\CoreBundle\Log;


use DateTime;

interface LogInterface
{
    public function getLevel() : string;
    public function getMessage() : string;
    public function getUsername() : ?string;
    public function getCreated() : DateTime;
}
