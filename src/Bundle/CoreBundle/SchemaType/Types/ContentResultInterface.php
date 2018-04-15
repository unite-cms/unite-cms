<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Knp\Component\Pager\Pagination\AbstractPagination;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentResultInterface extends InterfaceType
{

    public function __construct(SchemaTypeManager $schemaTypeManager, UniteCMSManager $uniteCMSManager)
    {
        parent::__construct(
            [
                'fields' => function () use ($schemaTypeManager) {
                    return [
                        'result' => Type::listOf($schemaTypeManager->getSchemaType('ContentInterface')),
                        'total' => Type::int(),
                        'page' => Type::int(),
                    ];
                },
                'resolveType' => function ($value) use ($schemaTypeManager, $uniteCMSManager) {
                    if (!$value instanceof AbstractPagination) {
                        throw new \InvalidArgumentException('Value must be instance of '.AbstractPagination::class.'.');
                    }
                    return $schemaTypeManager->getSchemaType($value->getPaginatorOption('alias'), $uniteCMSManager->getDomain());
                },
            ]
        );
    }
}
