<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class ContentTypeFieldValidator extends ConstraintValidator
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
     * @param \UniteCMS\CoreBundle\Validator\Constraints\ContentTypeField $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if(!$value instanceof ContentTypeField) {
            return;
        }

        // Validate field type.
        if(!$this->fieldTypeManager->hasFieldType($value->getType())) {
            $this->context
                ->buildViolation($constraint->invalid_type_message)
                ->setParameter('{{ field_type }}', $value->getType())
                ->addViolation();
            return;
        }

        // Allow field type to validate config.
        $this->fieldTypeManager
            ->getFieldType($value->getType())
            ->validate($value, $this->context);
    }
}
