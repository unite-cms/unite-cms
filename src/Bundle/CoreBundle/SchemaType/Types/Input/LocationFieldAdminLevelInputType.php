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

class LocationFieldAdminLevelInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'fields' => [
                'name' => Type::string(),
                'code' => Type::string(),
                'level' => Type::int(),
            ],
        ]);
    }
}
