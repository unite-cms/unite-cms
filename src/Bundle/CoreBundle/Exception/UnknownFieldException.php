<?php


namespace UniteCMS\CoreBundle\Exception;

use Exception;
use GraphQL\Error\ClientAware;

class UnknownFieldException extends Exception implements ClientAware
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message = "Unknown field.", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritDoc}
     */
    public function isClientSafe()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory()
    {
        return 'content';
    }
}
