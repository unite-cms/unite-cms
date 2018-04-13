<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Entity\View;

class DefaultViewTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $found = false;
        foreach ($value as $item) {
            if ($item instanceof View && $item->getIdentifier() == View::DEFAULT_VIEW_IDENTIFIER) {
                $found = true;
            }
        }

        if (!$found) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
