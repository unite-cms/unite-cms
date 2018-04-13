<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SettingInterface extends InterfaceType
{

    public function __construct(SchemaTypeManager $schemaTypeManager)
    {

        parent::__construct(
            [
                'fields' => function () use ($schemaTypeManager) {
                    return [
                        'type' => Type::string(),
                    ];
                },
                'resolveType' => function ($value) use ($schemaTypeManager) {
                    if (!$value instanceof Setting) {
                        throw new \InvalidArgumentException('Value must be instance of '.Setting::class.'.');
                    }

                    $type = ucfirst($value->getSettingType()->getIdentifier()).'Setting';

                    return $schemaTypeManager->getSchemaType($type);
                },
            ]
        );
    }
}
