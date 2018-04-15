<?php

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class FilterInput extends InputObjectType
{
    public function __construct(SchemaTypeManager $schemaTypeManager)
    {
        parent::__construct(
            [
                'fields' => function () use ($schemaTypeManager) {
                    return [
                        'AND' => [
                            'type' => Type::listOf($schemaTypeManager->getSchemaType('FilterInput')),
                            'description' => 'Adding multiple AND-filter conditions',
                        ],
                        'OR' => [
                            'type' => Type::listOf($schemaTypeManager->getSchemaType('FilterInput')),
                            'description' => 'Adding multiple OR-filter conditions',
                        ],
                        'operator' => [
                            'type' => Type::string(),
                            'description' => 'Set the operation to filter by',
                        ],
                        'field' => [
                            'type' => Type::string(),
                            'description' => 'Set the field to filter',
                        ],
                        'value' => [
                            'type' => Type::string(),
                            'description' => 'Set the field value',
                        ]
                    ];
                },
            ]
        );
    }
}
