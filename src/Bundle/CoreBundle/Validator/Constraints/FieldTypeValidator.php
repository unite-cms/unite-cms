<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class FieldTypeValidator extends ConstraintValidator
{
    /**
     * @var FieldTypeManager
     */
    private $typeManager;

    public function __construct(FieldTypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!is_string($value) || !$this->typeManager->hasFieldType($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
