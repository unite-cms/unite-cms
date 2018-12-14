<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class CheckboxFieldType extends FieldType
{
    const TYPE = "checkbox";
    const FORM_TYPE = CheckboxType::class;
    const SETTINGS = ['default', 'description'];

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'boolean', 'message' => 'invalid_initial_data']))
        );
    }
}
