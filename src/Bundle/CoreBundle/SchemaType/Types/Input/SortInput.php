<?php

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class SortInput extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'field' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The field to sort by.',
                    ],
                    'order' => [
                        'defaultValue' => 'ASC',
                        'type' => Type::string(),
                        'description' => 'The sort order. Must be ASC or DESC.',
                    ],
                    'ignore_case' => [
                        'defaultValue' => false,
                        'type' => Type::boolean(),
                        'description' => 'If set to true, the field will be transformed to lowercase before sorting. 
                        You only need this, if your database COLLATION is case sensitive and for text fields.',
                    ]
                ],
            ]
        );
    }
}
