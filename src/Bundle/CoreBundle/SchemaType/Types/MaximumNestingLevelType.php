<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MaximumNestingLevelType extends AbstractType
{
    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        return [
            'message' => Type::string(),
        ];
    }

    /**
     * Resolve fields for this type.
     * Returns the object or scalar value for the field, define in $info.
     *
     * @param mixed $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        return 'Maximum nesting level of ' . SchemaTypeManager::MAXIMUM_NESTING_LEVEL . ' reached.';
    }
}