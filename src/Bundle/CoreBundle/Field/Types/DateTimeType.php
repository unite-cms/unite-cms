<?php

namespace UniteCMS\CoreBundle\Field\Types;

use DateTime;
use GraphQL\Error\Error;

class DateTimeType extends AbstractFieldType
{
    const TYPE = 'dateTime';
    const GRAPHQL_INPUT_TYPE = 'DateTime';

    /**
     * @param $value
     *
     * @return \DateTime
     * @throws \GraphQL\Error\Error
     * @throws \Exception
     */
    static function parseValue($value) {

        if(empty($value)) {
            return $value;
        }

        if(is_int($value)) {
            $date = new DateTime();
            $date->setTimestamp($value);
            return $date;
        }

        else if(is_string($value)) {
            return new DateTime($value);
        }

        throw new Error("Date input must be a unix timestamp INT or a date string.");
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    protected function normalizeDefaultValue($data) {
        return static::parseValue($data);
    }
}
