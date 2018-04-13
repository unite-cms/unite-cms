<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.11.17
 * Time: 13:19
 */

namespace UniteCMS\CoreBundle\Field;

class FieldableFieldSettings
{
    public function __construct(array $settings = [])
    {
        foreach($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __set($name, $value) {
        $this->$name = $value;
        return $this;
    }

    public function __get($name) {
        if(isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }

    public function __isset($name) {
        return isset($this->$name);
    }
}
