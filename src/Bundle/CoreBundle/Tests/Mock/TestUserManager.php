<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

class TestUserManager extends TestContentManager implements UserManagerInterface {

    public function findByUsername(Domain $domain, string $type, string $username): ?UserInterface {
        $result = $this->find($domain, $type, ContentFilterInput::fromInput(['username' => $username]));
        return $result->getTotal() > 0 ? $result->getResult()[0] : null;
    }
}
