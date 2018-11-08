<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldType;

class IntegerFieldType extends FieldType
{
    const TYPE = "integer";
    const FORM_TYPE = IntegerType::class;

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue(ExecutionContextInterface $context, $value) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'integer', 'message' => 'invalid_initial_data']))
        );
    }
}