<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class FieldableContentInterface extends InterfaceType
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
                'resolveType' => function ($value, $context, ResolveInfo $info) use ($schemaTypeManager) {
                    if (!$value instanceof FieldableContent) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.FieldableContent::class.'.'
                        );
                    }

                    $type = IdentifierNormalizer::graphQLType($value->getEntity());
                    return $info->schema->hasType($type) ?
                        $info->schema->getType($type) : $schemaTypeManager->getSchemaType($type);
                },
            ]
        );
    }
}
