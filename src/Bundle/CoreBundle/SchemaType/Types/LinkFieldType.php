<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 18.09.18
 * Time: 17:43
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class LinkFieldType extends AbstractType
{
    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        return [
            'url' => Type::string(),
            'title' => Type::string(),
            'target' => Type::string()
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
        if (!is_array($value) or !array_key_exists($info->fieldName, $this->fields()))
        {
            throw new \InvalidArgumentException('Unknown fieldName "'.$info->fieldName.'"');
        }

        if (!isset($value[$info->fieldName])) 
        {
            $value[$info->fieldName] = null;
        }

        return $value[$info->fieldName];
        
    }
}