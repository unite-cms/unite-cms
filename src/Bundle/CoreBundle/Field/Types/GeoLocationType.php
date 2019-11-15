<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class GeoLocationType extends AbstractFieldType
{
    const TYPE = 'geoLocation';
    const GRAPHQL_INPUT_TYPE = 'UniteGeoLocationInput';

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UniteGeoLocation'];
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return [
            'provided_by' => $fieldData->resolveData('provided_by', null),
            'id' => $fieldData->resolveData('id', null),
            'category' => $fieldData->resolveData('category', null),
            'display_name' => $fieldData->resolveData('display_name', null),
            'latitude' => $fieldData->resolveData('latitude', null),
            'longitude' => $fieldData->resolveData('longitude', null),
            'bound_south' => $fieldData->resolveData('bound_south', null),
            'bound_west' => $fieldData->resolveData('bound_west', null),
            'bound_north' => $fieldData->resolveData('bound_north', null),
            'bound_east' => $fieldData->resolveData('bound_east', null),
            'country_code' => $fieldData->resolveData('country_code', null),
            'locality' => $fieldData->resolveData('locality', null),
            'sub_locality' => $fieldData->resolveData('sub_locality', null),
            'postal_code' => $fieldData->resolveData('postal_code', null),
            'street_name' => $fieldData->resolveData('street_name', null),
            'street_number' => $fieldData->resolveData('street_number', null),
            'stairs_number' => $fieldData->resolveData('stairs_number', null),
            'door_number' => $fieldData->resolveData('door_number', null),
        ];
    }
}
