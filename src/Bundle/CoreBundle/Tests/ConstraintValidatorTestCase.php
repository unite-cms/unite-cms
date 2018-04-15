<?php

namespace UniteCMS\CoreBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

abstract class ConstraintValidatorTestCase extends TestCase
{
    protected $constraintClass = null;
    protected $constraintValidatorClass = null;

    /**
     * Returns an ExecutionContext for the given ConstraintValidator and Constraint after initialization and value
     * validation.
     *
     * @param null $value
     * @param null|ConstraintValidator $constraintValidator
     * @param null|Constraint $constraint
     * @param null|mixed $object
     * @return ExecutionContext
     */
    protected function validate(
        $value = null,
        ConstraintValidator $constraintValidator = null,
        Constraint $constraint = null,
        $object = null
    ) {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->getMock();
        $validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')->getMock();
        $context = new ExecutionContext($validator, 'root', $translator);
        $constraint = $constraint ?? new $this->constraintClass();
        $constraintValidator = $constraintValidator ?? new $this->constraintValidatorClass();
        $context->setConstraint($constraint);
        $context->setNode($context->getValue(), $object, $context->getMetadata(), $context->getPropertyPath());
        $constraintValidator->initialize($context);
        $constraintValidator->validate($value, $constraint);

        return $context;
    }

}
