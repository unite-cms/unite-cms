<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class ValidContentValidator extends ConstraintValidator
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(DomainManager $domainManager, FieldTypeManager $fieldTypeManager)
    {
        $this->domainManager = $domainManager;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if(!$value instanceof ContentInterface) {
            return null;
        }

        $domain = $this->domainManager->current();
        $contentType = $domain->getContentTypeManager()->getAnyType($value->getType());

        // Allow all field types to validate field data.
        foreach($contentType->getFields() as $id => $field) {
            $fieldData = $value->getFieldData($id);
            $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
            $fieldType->validateFieldData($value, $field, $this->context, $fieldData);

            // Check all defined constraints for this field type.
            $this->context->getValidator()
                ->inContext($this->context)
                ->atPath('['.$id.']')
                ->validate($fieldData, $field->getConstraints());
        }

        // Check all defined constraints for this content type.
        $this->context->getValidator()
            ->inContext($this->context)
            ->validate($value, $contentType->getConstraints());
    }
}
