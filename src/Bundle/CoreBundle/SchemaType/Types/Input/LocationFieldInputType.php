<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 18.09.18
 * Time: 17:43
 */

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class LocationFieldInputType extends InputObjectType
{
    protected $schemaTypeManager;

    public function __construct(SchemaTypeManager $schemaTypeManager)
    {
        $this->schemaTypeManager = $schemaTypeManager;
        parent::__construct([
            'fields' => [
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
                'admin_levels' => Type::listOf($this->schemaTypeManager->getSchemaType('LocationFieldAdminLevelInput')),
                'country_code' => Type::string(),
            ],
        ]);
    }
}
