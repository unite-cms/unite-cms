<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class ValidPermissionsValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {

        $allowedAttributes = [];
        $allowedRoles = [];

        // Get all allowed attributes.
        if ($constraint->callbackAttributes) {
            if (!is_callable($allowedAttributes = array($this->context->getObject(), $constraint->callbackAttributes))
                && !is_callable(
                    $allowedAttributes = array($this->context->getClassName(), $constraint->callbackAttributes)
                )
                && !is_callable($allowedAttributes = $constraint->callbackAttributes)
            ) {
                throw new ConstraintDefinitionException(
                    'The ValidPermission constraint expects a valid allowedAttributesCallback'
                );
            }
            $allowedAttributes = call_user_func($allowedAttributes);
        }

        // Get all allowed roles.
        if ($constraint->callbackRoles) {
            if (!is_callable($allowedRoles = array($this->context->getObject(), $constraint->callbackRoles))
                && !is_callable($allowedRoles = array($this->context->getClassName(), $constraint->callbackRoles))
                && !is_callable($allowedRoles = $constraint->callbackRoles)
            ) {
                throw new ConstraintDefinitionException(
                    'The ValidPermission constraint expects a valid allowedRolesCallback'
                );
            }
            $allowedRoles = call_user_func($allowedRoles);
        }

        foreach ($value as $attribute => $roles) {
            if (!in_array($attribute, $allowedAttributes)) {
                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }

            if (!is_array($roles)) {
                throw new InvalidArgumentException(
                    'The ValidPermission constraint expects an nested array as value'
                );
            }

            foreach ($roles as $role) {
                if (!in_array($role, $allowedRoles)) {
                    $this->context->buildViolation($constraint->message)
                        ->addViolation();

                    return;
                }
            }
        }
    }
}