<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldType;

class EmailFieldType extends FieldType
{
    const TYPE = "email";
    const FORM_TYPE = EmailType::class;

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue(ExecutionContextInterface $context, $value) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Email(['message' => 'invalid_initial_data']))
        );
    }
}