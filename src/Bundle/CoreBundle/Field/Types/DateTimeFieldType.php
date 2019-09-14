<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class DateTimeFieldType extends DateFieldType
{
    const TYPE = "datetime";
    const FORM_TYPE = DateTimeType::class;
    const DATE_FORMAT = 'Y-m-d H:i:s';

    const SETTINGS = ['not_empty', 'description', 'default', 'form_group', 'min', 'max', 'step'];

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
    protected function transformMinMaxValue($value) {
        return str_replace(' ', 'T', $value);
    }
}
