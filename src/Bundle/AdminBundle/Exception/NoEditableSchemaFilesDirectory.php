<?php


namespace UniteCMS\AdminBundle\Exception;

use GraphQL\Error\UserError;
use Throwable;

class NoEditableSchemaFilesDirectory extends UserError
{
    const MESSAGE = 'No editable schema files directory is configured for this domain.';

    public function __construct($message = self::MESSAGE, $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
