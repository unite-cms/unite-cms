<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\ContentType\ContentType;
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
        $contentType = $this->context->getObject();

        if(!$contentType instanceof ContentType) {
            return;
        }

        $fields = is_array($value) ? $value : [$value];

        foreach($fields as $field) {
            if(!$field instanceof ContentTypeField) {
                continue;
            }

            // Validate field type.
            if(!$this->fieldTypeManager->hasFieldType($field->getType())) {
                $this->context
                    ->buildViolation($constraint->invalid_type_message)
                    ->setParameter('{{ field_type }}', $field->getType())
                    ->addViolation();
                return;
            }

            // Allow field type to validate config.
            $this->fieldTypeManager
                ->getFieldType($field->getType())
                ->validateFieldDefinition($contentType, $field, $this->context);
        }
    }
}
