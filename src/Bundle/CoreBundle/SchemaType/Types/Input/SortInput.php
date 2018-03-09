<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types\Input;

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
                ],
            ]
        );
    }
}