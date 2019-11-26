<?php


namespace UniteCMS\AdminBundle\Exception;

use GraphQL\Error\UserError;
use Throwable;

class InvalidAdminViewFieldConfig extends UserError
{
    const MESSAGE = 'Invalid admin view field configuration.';

    public function __construct($message = self::MESSAGE, $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
