<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class ValidFieldableContentDataValidator extends ConstraintValidator
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
        if (!is_array($value) || !$this->context->getObject()) {
            return;
        }

        if (!$this->context->getObject() instanceof FieldableContent) {
            throw new InvalidArgumentException(
                'The ValidFieldableContentDataValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableContent object.'
            );
        }

        if (!$this->context->getObject()->getEntity()) {
            return;
        }

        if (!$this->context->getObject()->getEntity() instanceof Fieldable) {
            throw new InvalidArgumentException(
                'The ValidFieldableContentDataValidator constraint expects object->getEntity() to return a UniteCMS\CoreBundle\Entity\Fieldable object.'
            );
        }

        /**
         * @var FieldableContent $content
         */
        $content = $this->context->getObject();
        $content_fields = $content->getEntity()->getFields()->getKeys();

        // make sure, that the content unit contains no additional data.
        if (count(array_diff(array_keys($value), $content_fields)) > 0) {
            $this->context->buildViolation($constraint->additionalDataMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setInvalidValue($value)
                ->addViolation();

            return;
        }

        foreach ($value as $field_key => $field_value) {
            $field = $content->getEntity()->getFields()->get($field_key);
            $this->fieldTypeManager->validateFieldData($field, $field_value, $this->context);
        }
    }
}
