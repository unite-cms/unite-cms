<?php


namespace UniteCMS\CoreBundle\Exception;

use GraphQL\Error\ClientAware;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Throwable;

class ConstraintViolationsException extends ValidatorException implements ClientAware
{

    /**
     * @var ConstraintViolationListInterface|\Symfony\Component\Validator\ConstraintViolation[] $violations
     */
    protected $violations;

    public function __construct(ConstraintViolationListInterface $violations, $message = "%s", $code = 0, Throwable $previous = null) {
        $this->violations = $violations;

        $errorMessages = [];

        foreach($this->violations as $violation) {
            $errorMessages[] = (empty($violation->getPropertyPath()) ? '' : $violation->getPropertyPath() . ': ') . $violation->getMessage();
        }

        $message = sprintf($message, join(', ', $errorMessages));
        parent::__construct($message, $code, $previous);
    }

    public function getViolations() : ConstraintViolationListInterface {
        return $this->violations;
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
        return 'violation';
    }
}
