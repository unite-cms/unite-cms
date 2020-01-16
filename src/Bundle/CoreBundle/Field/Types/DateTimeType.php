<?php

namespace UniteCMS\CoreBundle\Field\Types;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use GraphQL\Error\Error;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;

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

        if($fieldData->empty() && !$field->isNonNull() && !$field->isListOf()) {
            return null;
        }

        $timestamp = $fieldData->resolveData('', 0);
        $date = new DateTime();
        $date->setTimestamp((int)$timestamp);
        return $date;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        $settings = parent::getPublicSettings($field);

        if($settings && !empty($settings->get('default'))) {
            $settings->set('default', $settings->get('default')->format('c'));
        }

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function queryComparison(ContentTypeField $field, array $whereInput) : ?BaseFieldComparison {

        // Convert any input (int, string etc.) to unix timestamp
        if(!empty($whereInput['value'])) {
            $whereInput['value'] = is_array($whereInput['value']) ? $whereInput['value'] : [$whereInput['value']];
            $whereInput['value'] = array_map(function($value){
                return static::parseValue($value)->getTimestamp();
            }, $whereInput['value']);
        }

        return parent::queryComparison($field, $whereInput);
    }
}
