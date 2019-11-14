<?php


namespace UniteCMS\AdminBundle\Exception;

use GraphQL\Error\UserError;
use Throwable;

class InvalidAdminViewType extends UserError
{
    const MESSAGE = '@adminView fragment is only allowed on UniteContent, UniteUser or UniteSingleContent types.';

    public function __construct($message = self::MESSAGE, $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
