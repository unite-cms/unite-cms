<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;

class ValidFieldableContentLocaleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if($this->context->getObject() == null) {
          return;
        }

        if (!$this->context->getObject() instanceof FieldableContent) {
            throw new InvalidArgumentException(
                'The ValidFieldableContentLocaleValidator constraint expects a UnitedCMS\CoreBundle\Entity\FieldableContent object.'
            );
        }

      if (!empty($this->context->getObject()->getEntity()) && !$this->context->getObject()->getEntity() instanceof Fieldable) {
        throw new InvalidArgumentException(
          'The ValidFieldableContentLocaleValidator constraint expects object->getEntity() to return a UnitedCMS\CoreBundle\Entity\Fieldable object.'
        );
      }

        /**
         * @var FieldableContent $content
         */
        $content = $this->context->getObject();

        // If there is no content type or this content type does not support localization, this field must be empty.
        if (empty($content->getEntity()) || empty($content->getEntity()->getLocales())) {
            if($value != null) {
                $this->context->buildViolation($constraint->message)
                    ->setInvalidValue(null)
                    ->atPath('[locale]')
                    ->addViolation();
            }
            return;
        }

        // If it is empty, we we cannot validate it.
        if($value == null) {
            return;
        }

        if(!in_array($value, $content->getEntity()->getLocales())) {
            $this->context->buildViolation($constraint->message)
                ->setInvalidValue($value)
                ->atPath('[locale]')
                ->addViolation();
        }
    }
}