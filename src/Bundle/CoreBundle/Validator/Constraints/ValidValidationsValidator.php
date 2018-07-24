<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use UniteCMS\CoreBundle\Field\FieldableValidation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Security\ValidationExpressionChecker;

class ValidValidationsValidator extends ConstraintValidator
{

    /**
     * @var ValidationExpressionChecker $expressionChecker
     */
    private $expressionChecker;

    public function __construct()
    {
        $this->expressionChecker = new ValidationExpressionChecker();
    }

    public function validate($value, Constraint $constraint)
    {
        if(!is_array($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
            return;
        }

        foreach($value as $index => $validation) {

            if(!$validation instanceof FieldableValidation) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }

            if(empty($validation->getExpression())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index]")->addViolation();
                return;
            }

            if(!is_string($validation->getExpression()) || !is_string($validation->getMessage()) || !is_string($validation->getPath())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index]")->addViolation();
                return;
            }

            if(!$this->expressionChecker->validate($validation->getExpression())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index][expression]")->addViolation();
                return;
            }

            foreach($validation->getGroups() as $group) {
                if(!in_array($group, ['CREATE', 'UPDATE', 'DELETE'])) {
                    $this->context->buildViolation($constraint->message)->atPath("[$index][group]")->addViolation();
                    return;
                }
            }
        }
    }
}
