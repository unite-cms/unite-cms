<?php

namespace UniteCMS\CoreBundle\Field\Types;

use DateTime;
use GraphQL\Error\Error;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

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

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null) : FieldData {
        $fieldData = parent::normalizeInputData($content, $field, $inputData);
        return $fieldData->empty() ? $fieldData : new FieldData($fieldData->getData()->getTimestamp());
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        $timestamp = $fieldData->resolveData('', $field->isNonNull() ? 0 : null);
        $date = new DateTime();
        $date->setTimestamp((int)$timestamp);
        return $date;
    }
}
