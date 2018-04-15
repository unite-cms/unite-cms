<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\View\ViewTypeManager;

class ViewTypeValidator extends ConstraintValidator
{
    /**
     * @var ViewTypeManager
     */
    private $typeManager;

    public function __construct(ViewTypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!is_string($value) || !$this->typeManager->hasViewType($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
