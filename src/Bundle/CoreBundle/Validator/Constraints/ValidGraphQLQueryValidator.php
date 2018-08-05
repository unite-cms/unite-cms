<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidGraphQLQueryValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {
        if(empty($value)) {
            return;
        }

        // At this point we can only validate query syntax but no real schemas.
        try {
            Parser::parse($value);
        } catch (SyntaxError $error) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
