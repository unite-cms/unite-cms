<?php

namespace UniteCMS\CoreBundle\Tests\Mocks;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeAlterationInterface;

class SchemaTypeAlterationMock implements SchemaTypeAlterationInterface
{
    protected $schemaTypeName;

    public function __construct(string $schemaTypeName)
    {
        $this->schemaTypeName = $schemaTypeName;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $schemaTypeName): bool
    {
        return $schemaTypeName === $this->schemaTypeName;
    }

    /**
     * {@inheritDoc}
     */
    public function alter(Type $schemaType): void
    {
        $originalFn = $schemaType->config['fields'];
        $schemaType->config['fields'] = function() use ($originalFn) {
            return array_merge($originalFn(), ['altered' => Type::string()]);
        };
        $originalFn = $schemaType->config['resolveField'];
        $schemaType->config['resolveField'] = function($value, array $args, $context, ResolveInfo $info) use ($originalFn) {
            if($info->fieldName === $this->schemaTypeName) {
                return 'Altered value';
            }
            return $originalFn($value, $args, $context, $info);
        };
    }
}