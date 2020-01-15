<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Security\User\BaseUser;

class TestUser extends BaseUser {

    public function __construct(string $type, array $data = [])
    {
        parent::__construct($type);
        $this->data = $data;
    }

    public function setId() : self {
        $this->id = uniqid();
        return $this;
    }
}
