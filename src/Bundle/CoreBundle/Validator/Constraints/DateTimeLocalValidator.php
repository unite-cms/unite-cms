<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use DateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateTimeLocalValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $date = DateTime::createFromFormat($constraint->format, $value);

        // Ensure format includes leading zeroes
        if (!preg_match("/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/", $value) || !$date) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
