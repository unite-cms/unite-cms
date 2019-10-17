<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Field\Types\UserNameType;
use UniteCMS\CoreBundle\Field\Types\UserPasswordType;
use UniteCMS\CoreBundle\UserType\UserType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

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

        $userNameFound = false;

        foreach($contentType->getFields() as $field) {
            if($field->getType() === UserNameType::getType()) {
                $userNameFound = true;
            }
        }

        if(!$userNameFound) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
