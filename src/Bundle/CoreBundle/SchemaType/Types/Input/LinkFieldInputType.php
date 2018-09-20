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

class LinkFieldInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'url' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The url',
                    ],
                    'title' => [
                        'type' => Type::string(),
                        'description' => 'The Link Title',
                    ],
                    'target' => [
                        'type' => Type::string(),
                        'description' => 'The Link Target',
                    ]
                ],
            ]
        );
    }
}