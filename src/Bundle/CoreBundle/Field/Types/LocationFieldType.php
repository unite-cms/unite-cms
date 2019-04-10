<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\LocationType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class LocationFieldType extends FieldType
{
    const TYPE = "location";
    const FORM_TYPE = LocationType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['description'];

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return [
            'provided_by' => null,
            'id' => null,
            'category' => null,
            'display_name' => null,
            'latitude' => null,
            'longitude' => null,
            'bound_south' => null,
            'bound_west' => null,
            'bound_north' => null,
            'bound_east' => null,
            'street_number' => null,
            'street_name' => null,
            'postal_code' => null,
            'locality' => null,
            'sub_locality' => null,
            'admin_levels' => [],
            'country_code' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData) {
        $fieldData = $data[$field->getIdentifier()];

        $fieldData['latitude'] = (float)$fieldData['latitude'];
        $fieldData['longitude'] = (float)$fieldData['longitude'];
        $fieldData['bound_south'] = (float)$fieldData['bound_south'];
        $fieldData['bound_west'] = (float)$fieldData['bound_west'];
        $fieldData['bound_north'] = (float)$fieldData['bound_north'];
        $fieldData['bound_east'] = (float)$fieldData['bound_east'];

        if(!empty($fieldData['adminLevels'])) {
            foreach($fieldData['adminLevels'] as $delta => $row) {
                $fieldData['adminLevels'][$delta]['level'] = (int)$row['level'];
            }
        }

        $data[$field->getIdentifier()] = $fieldData;
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('LocationField');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('LocationFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        return (array) $value;
    }
}
