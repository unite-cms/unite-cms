<?php

namespace UniteCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class ReferenceFieldTypeInput extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'domain' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The domain of the content to reference.',
                    ],
                    'content_type' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The content type of the content to reference.',
                    ],
                    'content' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The content to reference.',
                    ],
                ],
            ]
        );
    }
}
