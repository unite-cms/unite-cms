<?php


namespace UniteCMS\CoreBundle\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidContentVersionException extends InvalidArgumentException
{
    public function __construct($message = "Invalid content version.", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
