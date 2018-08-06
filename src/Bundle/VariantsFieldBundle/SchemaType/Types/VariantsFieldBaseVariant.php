<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 06.08.18
 * Time: 09:39
 */

namespace UniteCMS\VariantsFieldBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\AbstractType;
use UniteCMS\VariantsFieldBundle\Model\Variant;

class VariantsFieldBaseVariant extends AbstractType
{
    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    public function __construct(SchemaTypeManager $schemaTypeManager) {
        $this->schemaTypeManager = $schemaTypeManager;
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
            'type' => Type::string(),
        ];
    }

    /**
     * Define all interfaces, this type implements.
     *
     * @return array
     */
    protected function interfaces() {
        return [ $this->schemaTypeManager->getSchemaType('VariantsFieldInterface') ];
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
        if(!$value instanceof Variant) {
            throw new \InvalidArgumentException(
                'Value must be instance of '.Variant::class.'.'
            );
        }

        if($info->fieldName === 'type') {
            return $value->getIdentifier();
        }
        return null;
    }
}