<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ValidFieldSettingsValidator extends ConstraintValidator
{
    /**
     * @var FieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Adds a new ConstraintViolation to the current context. Takes the violation and only modify the propertyPath to
     * make the violation a child of this field.
     *
     * @param ConstraintViolation $violation
     */
    private function addDataViolation(ConstraintViolation $violation)
    {
        $this->context->getViolations()->add(
            new ConstraintViolation(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getRoot(),
                $this->context->getPropertyPath($violation->getPropertyPath()),
                $violation->getInvalidValue(),
                $violation->getPlural(),
                $violation->getCode(),
                $violation->getConstraint()
            )
        );
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldableFieldSettings) {
            throw new InvalidArgumentException(
                'The ValidFieldSettingsValidator constraint expects a UniteCMS\CoreBundle\Field\FieldableFieldSettings value.'
            );
        }

        if (!$this->context->getObject() instanceof FieldableField) {
            throw new InvalidArgumentException(
                'The ValidFieldSettingsValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableField object.'
            );
        }

        if($this->fieldTypeManager->hasFieldType($this->context->getObject()->getType())) {
            foreach ($this->fieldTypeManager->validateFieldSettings(
                $this->context->getObject(),
                $value
            ) as $violation) {
                $this->addDataViolation($violation);
            }
        }
    }
}
