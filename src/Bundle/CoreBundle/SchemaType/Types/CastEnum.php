<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-03-26
 * Time: 12:27
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\EnumType;

class CastEnum extends EnumType
{
    const CAST_INTEGER = 'INTEGER';
    const CAST_FLOAT = 'FLOAT';
    const CAST_BOOLEAN = 'BOOLEAN';
    const CAST_DATE = 'DATE';
    const CAST_DATETIME = 'DATETIME';

    public function __construct()
    {
        parent::__construct([
            'name' => 'Cast',
            'description' => 'Cast transformations, that are allowed in different places to cast a string to another type.',
            'values' => [
                'CAST_INTEGER' => [
                    'value' => static::CAST_INTEGER,
                ],
                'CAST_FLOAT' => [
                    'value' => static::CAST_FLOAT,
                ],
                'CAST_BOOLEAN' => [
                    'value' => static::CAST_BOOLEAN,
                ],
                'CAST_DATE' => [
                    'value' => static::CAST_DATE,
                ],
                'CAST_DATETIME' => [
                    'value' => static::CAST_DATETIME,
                ],
            ],
        ]);
    }
}
