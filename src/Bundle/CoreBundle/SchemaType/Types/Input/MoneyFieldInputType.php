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

class MoneyFieldInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'value' => [
                        'type' => Type::float(),
                        'description' => 'The money value as a float number.',
                    ],
                    'currency' => [
                        'type' => Type::string(),
                        'description' => 'The money currency as ISO 4217 code.',
                    ]
                ],
            ]
        );
    }
}