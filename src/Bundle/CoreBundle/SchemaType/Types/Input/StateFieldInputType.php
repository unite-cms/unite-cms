<?php
/**
 * Created by PhpStorm.
 * User: stefan.kamsker
 * Date: 27.09.18
 * Time: 10:29
 */

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class StateFieldInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'transition' => [
                        'type' => Type::string(),
                        'description' => 'The desired State Transition',
                    ]
                ],
            ]
        );
    }
}