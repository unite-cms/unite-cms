<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ReservedWordsValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {

        $reserved = [];

        if (is_array($constraint->reserved)) {
            $reserved = $constraint->reserved;
        } elseif (is_string($constraint->reserved) && constant($constraint->reserved)) {
            $reserved = constant($constraint->reserved);
        }

        if (in_array((string)$value, $reserved)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}