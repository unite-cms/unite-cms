<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Security\ValidationExpressionChecker;

class ValidFieldableContentDataValidator extends ConstraintValidator
{
    /**
     * @var ValidationExpressionChecker $expressionChecker
     */
    private $expressionChecker;

    /**
     * @var FieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->expressionChecker = new ValidationExpressionChecker();
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

        // allow the field type to validate data.
        foreach ($value as $field_key => $field_value) {
            $field = $content->getEntity()->getFields()->get($field_key);
            $this->fieldTypeManager->validateFieldData($field, $field_value, $this->context);
        }

        // call all validators for the fieldable type if they are enabled for the current context group.
        $group = $this->context->getGroup();
        if($group != 'DELETE') {
            $group = (method_exists($content, 'getId') && $content->getId()) ? 'UPDATE' : 'CREATE';
        }

        foreach ($content->getEntity()->getValidations() as $validation) {
            if(in_array($group, $validation->getGroups()) && !$this->expressionChecker->evaluate($validation->getExpression(), $content)) {
                $path = join('][', explode('.', $validation->getPath()));
                $message = empty($validation->getMessage()) ? 'Invalid value' : $validation->getMessage();

                if(!empty($path)) {
                    $path = '['.$path.']';
                }

                $this->context->buildViolation($message)->atPath($path)->addViolation();
            }
        }
    }
}
