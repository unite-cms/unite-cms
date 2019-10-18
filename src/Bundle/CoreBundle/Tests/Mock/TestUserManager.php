<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\User\UserInterface;
use UniteCMS\CoreBundle\User\UserManagerInterface;

class TestUserManager implements UserManagerInterface
{
    public function find(
        Domain $domain,
        string $type,
        ContentFilterInput $filter = null,
        array $orderBy = [],
        int $limit = 20,
        int $offset = 0,
        bool $includeDeleted = false,
        ?callable $resultFilter = null
    ): ContentResultInterface {
        // TODO: Implement find() method.
    }

    public function get(
        Domain $domain,
        string $type,
        string $id
    ): ?ContentInterface {
        // TODO: Implement get() method.
    }

    public function create(
        Domain $domain,
        string $type
    ): ContentInterface {
        // TODO: Implement create() method.
    }

    public function update(
        Domain $domain,
        ContentInterface $content,
        array $inputData = [],
        bool $persist = false
    ): ContentInterface {
        // TODO: Implement update() method.
    }

    public function delete(
        Domain $domain,
        string $type,
        ContentInterface $content,
        bool $persist = false
    ): ContentInterface {
        // TODO: Implement delete() method.
    }

    public function findByUsername(
        Domain $domain,
        string $type,
        string $username
    ): ?UserInterface {
        // TODO: Implement findByUsername() method.
    }
}
