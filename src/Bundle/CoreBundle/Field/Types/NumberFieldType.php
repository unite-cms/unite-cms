<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldType;
use Symfony\Component\Validator\Constraints as Assert;

class NumberFieldType extends FieldType
{
    const TYPE = "number";
    const FORM_TYPE = NumberType::class;

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue(ExecutionContextInterface $context, $value) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'numeric', 'message' => 'invalid_initial_data']))
        );
    }
}