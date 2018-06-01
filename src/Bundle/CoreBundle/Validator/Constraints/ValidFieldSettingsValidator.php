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
            $this->fieldTypeManager->validateFieldSettings($value, $this->context);
        }
    }
}
