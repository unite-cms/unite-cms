<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

abstract class AbstractType extends ObjectType
{

    public function __construct()
    {
        parent::__construct(
            [
                'fields' => function () {
                    return $this->fields();
                },
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) {
                    return $this->resolveField($value, $args, $context, $info);
                },
                'interfaces' => function () {
                    return $this->interfaces();
                },
            ]
        );
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    abstract protected function fields();

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
    abstract protected function resolveField($value, array $args, $context, ResolveInfo $info);

    /**
     * Define all interfaces, this type implements.
     *
     * @return array
     */
    protected function interfaces()
    {
        return [];
    }

}
