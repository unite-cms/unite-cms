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

        if($this->viewTypeManager->hasViewType($this->context->getObject()->getType())) {
            $this->viewTypeManager->validateViewSettings($value, $this->context);
        }
    }
}
