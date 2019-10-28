<?php


namespace UniteCMS\CoreBundle\GraphQL;

use GraphQL\Error\FormattedError;
use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;

class ErrorFormatter extends FormattedError
{
    const VALIDATION_MESSAGE = 'Content is not valid, Please see "violations" extension.';
    const VALIDATION_CATEGORY = 'validation';

    /**
     * {@inheritDoc}
     */
    public static function createFromException($e, $debug = false, $internalErrorMessage = null)
    {
        // Default: Use GraphQL formatted error.
        $exception = parent::createFromException($e, $debug, $internalErrorMessage);

        // Based on the previous exception, we handle our custom exceptions.
        if(!empty($e->getPrevious())) {

            // Handle constraint violation exceptions.
            if ($e->getPrevious() instanceof ConstraintViolationsException) {
                $exception['message'] = static::VALIDATION_MESSAGE;
                $exception['extensions']['category'] = static::VALIDATION_CATEGORY;
                $exception['extensions']['violations'] = array_map(function(ConstraintViolation $violation){
                    return [
                        'message' => $violation->getMessage(),
                        'path' => $violation->getPropertyPath(),
                    ];
                }, iterator_to_array($e->getPrevious()->getViolations()));
            }
        }

        return $exception;
    }
}
