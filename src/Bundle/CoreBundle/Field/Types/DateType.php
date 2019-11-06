<?php

namespace UniteCMS\CoreBundle\Field\Types;

class DateType extends DateTimeType
{
    const TYPE = 'date';
    const GRAPHQL_INPUT_TYPE = 'Date';

    /**
     * {@inheritDoc}
     */
    static function parseValue($value) {
        $value = parent::parseValue($value);
        $value->setTime(0, 0, 0);
        return $value;
    }
}
