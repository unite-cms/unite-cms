<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 18.09.18
 * Time: 17:43
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class LocationFieldType extends AbstractType
{
    protected $schemaTypeManager;

    public function __construct(SchemaTypeManager $schemaTypeManager)
    {
        $this->schemaTypeManager = $schemaTypeManager;
        parent::__construct();
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        return [
            'provided_by' => Type::string(),
            'id' => Type::ID(),
            'category' => Type::string(),
            'display_name' => Type::string(),
            'latitude' => Type::float(),
            'longitude' => Type::float(),
            'bound_south' => Type::float(),
            'bound_west' => Type::float(),
            'bound_north' => Type::float(),
            'bound_east' => Type::float(),
            'street_number' => Type::string(),
            'street_name' => Type::string(),
            'postal_code' => Type::string(),
            'locality' => Type::string(),
            'sub_locality' => Type::string(),
            'admin_levels' => Type::listOf($this->schemaTypeManager->getSchemaType('LocationFieldAdminLevel')),
            'country_code' => Type::string(),
            'country_name' => Type::string(),
        ];
    }

    /**
     * Resolve fields for this type.
     * Returns the object or scalar value for the field, define in $info.
     *
     * @param mixed $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        if (!is_array($value) or !array_key_exists($info->fieldName, $this->fields()))
        {
            throw new \InvalidArgumentException('Unknown fieldName "'.$info->fieldName.'"');
        }

        if (!isset($value[$info->fieldName]))
        {
            $value[$info->fieldName] = null;
        }

        return $value[$info->fieldName];

    }
}