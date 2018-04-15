<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 11:44
 */

namespace UniteCMS\StorageBundle\SchemaType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class StorageFileInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The filename (with filetype suffix)',
                    ],
                    'size' => [
                        'type' => Type::nonNull(Type::int()),
                        'description' => 'The filesize in bytes',
                    ],
                    'type' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The MIME type',
                    ],
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'An UUID identifier for this file',
                    ],
                    'checksum' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'A checksum for uuid and filename, created by unite CMS.',
                    ],
                ],
            ]
        );
    }
}
