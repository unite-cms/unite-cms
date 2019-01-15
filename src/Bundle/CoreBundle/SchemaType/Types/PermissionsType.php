<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-17
 * Time: 09:24
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class PermissionsType extends AbstractType
{
    /**
     * @var $permissions
     */
    private $permissions;

    private static function normalizeKey(string $key = '') {
        return strtoupper(str_replace(' ', '_', $key));
    }

    public function __construct(array $permissions = [], string $name = null)
    {
        $this->permissions = array_map([PermissionsType::class, 'normalizeKey'], $permissions);
        parent::__construct();
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    protected function fields()
    {
        $fields = [];
        foreach($this->permissions as $permission) {
            $fields[$permission] = Type::nonNull(Type::boolean());
        }
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        foreach($value as $key => $access) {
            $key = PermissionsType::normalizeKey($key);
            if($info->fieldName === $key && in_array($key, $this->permissions)) {
                return $access;
            }
        }

        return null;
    }
}
