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

        $context = $this->context;

        // Check all defined constraints for this content type.
        $context
            ->getValidator()
            ->inContext($context)
            ->validate($value, $contentType->getConstraints(), [$context->getGroup()]);

        // Check all defined constraints for all fields + allow field types to validate
        foreach($contentType->getFields() as $id => $field) {

            $fieldData = $value->getFieldData($id);
            $fieldContext = $context->getValidator()->startContext()->atPath($context->getPropertyPath('['.$id.']'));

            // Check all defined constraints for this field type.
            $fieldContext->validate($fieldData, $field->getConstraints(), [$context->getGroup()]);

            // Allow all field types to validate field data.
            $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
            $fieldType->validateFieldData($value, $field, $fieldContext, $context, $fieldData);

            // Add field violations to general validator.
            $context->getViolations()->addAll($fieldContext->getViolations());
        }
    }
}
