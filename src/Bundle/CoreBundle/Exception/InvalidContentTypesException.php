<?php


namespace UniteCMS\CoreBundle\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Throwable;

class InvalidContentTypesException extends ValidatorException
{

    /**
     * @var ConstraintViolationListInterface $errors
     */
    protected $errors;

    public function __construct(ConstraintViolationListInterface $errors, $message = "Defined content types are not valid. Please check your schema!", $code = 0, Throwable $previous = null) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getErrors() : ConstraintViolationListInterface {
        return $this->errors;
    }
}
