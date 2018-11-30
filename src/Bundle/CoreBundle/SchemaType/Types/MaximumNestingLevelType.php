<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MaximumNestingLevelType extends AbstractType
{
    /**
     * @var int $maximumNestingLevel
     */
    private $maximumNestingLevel;

    public function __construct(int $maximumNestingLevel = 8)
    {
        $this->maximumNestingLevel = $maximumNestingLevel;
        parent::__construct();
    }

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
        return 'Maximum nesting level of ' . $this->maximumNestingLevel . ' reached.';
    }
}
