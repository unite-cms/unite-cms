<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.11.17
 * Time: 13:19
 */

namespace UniteCMS\CoreBundle\View;

class ViewSettings
{
    public function __construct(array $settings = [])
    {
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    public function processableConfig($root = 'settings') {
        return [
            $root => (array)$this,
        ];
    }
}
