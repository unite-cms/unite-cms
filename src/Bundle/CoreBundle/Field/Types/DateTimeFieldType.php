<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class DateTimeFieldType extends DateFieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['type'] = 'date';
    }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue(ExecutionContextInterface $context, $value) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\DateTime(['message' => 'invalid_initial_data']))
        );
    }
}