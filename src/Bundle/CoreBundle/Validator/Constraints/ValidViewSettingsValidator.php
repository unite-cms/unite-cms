<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Entity\View;

class ValidViewSettingsValidator extends ConstraintValidator
{
    /**
     * @var ViewTypeManager
     */
    private $viewTypeManager;

    public function __construct(ViewTypeManager $viewTypeManager)
    {
        $this->viewTypeManager = $viewTypeManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ViewSettings) {
            throw new InvalidArgumentException(
                'The ValidViewSettingsValidator constraint expects a UniteCMS\CoreBundle\View\ViewSettings value.'
            );
        }

        if (!$this->context->getObject() instanceof View) {
            throw new InvalidArgumentException(
                'The ValidViewSettingsValidator constraint expects a UniteCMS\CoreBundle\Entity\View object.'
            );
        }

        if ($this->viewTypeManager->hasViewType($this->context->getObject()->getType())) {
            foreach ($this->viewTypeManager->validateViewSettings(
                $this->context->getObject(),
                $value
            ) as $violation) {
                $this->addDataViolation($violation);
            }
        }
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
}
