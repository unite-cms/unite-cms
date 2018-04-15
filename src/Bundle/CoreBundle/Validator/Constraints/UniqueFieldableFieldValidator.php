<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class UniqueFieldableFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldableField) {
            throw new InvalidArgumentException(
                'The UniqueFieldableFieldValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableField value.'
            );
        }

        if($value->getEntity()) {
            $identifier = $value->getIdentifier();
            foreach ($value->getEntity()->getFields() as $field) {
                if ($field->getIdentifier() == $identifier && $field !== $value) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ identifier }}', $this->formatValue($identifier))
                        ->atPath('identifier')
                        ->setInvalidValue($identifier)
                        ->addViolation();
                }
            }
        }
    }
}
