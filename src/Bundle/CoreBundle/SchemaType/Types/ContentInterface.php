<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentInterface extends InterfaceType
{

    public function __construct(SchemaTypeManager $schemaTypeManager)
    {

        parent::__construct(
            [
                'fields' => function () use ($schemaTypeManager) {
                    return [
                        'id' => Type::id(),
                        'type' => Type::string(),
                        'created' => Type::int(),
                        'updated' => Type::int(),
                    ];
                },
                'resolveType' => function ($value) use ($schemaTypeManager) {
                    if (!$value instanceof Content) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.Content::class.'.'
                        );
                    }

                    $type = ucfirst($value->getContentType()->getIdentifier()).'Content';

                    return $schemaTypeManager->getSchemaType($type);
                },
            ]
        );
    }
}