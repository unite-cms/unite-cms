<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\ContentType\UserType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class UserTypeValidator extends ConstraintValidator
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param \UniteCMS\CoreBundle\Validator\Constraints\UserType $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $contentType = $this->context->getObject();

        if(!$contentType instanceof UserType) {
            return;
        }

        if(!array_key_exists('username', $contentType->getFields())) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
