<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 11:44
 */

namespace UniteCMS\StorageBundle\SchemaType;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\Types\AbstractType;

class StorageFileType extends AbstractType
{
    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        return [
            'name' => Type::string(),
            'size' => Type::int(),
            'type' => Type::string(),
            'id' => Type::id(),
            'url' => Type::string(),
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
        if (is_array($value) && array_key_exists($info->fieldName, $this->fields()) && isset($value[$info->fieldName])) {
            return $value[$info->fieldName];
        }
        throw new \InvalidArgumentException('Unknown fieldName "' . $info->fieldName . '"');
    }
}
