<?php


namespace UniteCMS\AdminBundle\Exception;

use GraphQL\Error\UserError;
use Throwable;

class InvalidAdminViewSelection extends UserError
{
    const MESSAGE = 'You selected an invalid field for an @adminView fragment. Please use only your content fields or "id".';

    public function __construct($message = self::MESSAGE, $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
