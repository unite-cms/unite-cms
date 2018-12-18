<?php
/**
 * Created by PhpStorm.
 * User: stefan.kamsker
 * Date: 19.08.18
 * Time: 11:44
 */

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class AutoTextFieldInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'text' => [
                        'type' => Type::string(),
                        'description' => 'If auto is set to false, this custom text will be the value of the field.',
                    ],
                    'auto' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'If set to true, the value of this field will be generated automatically.',
                    ],
                ],
            ]
        );
    }
}