<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use UniteCMS\DoctrineORMBundle\Entity\User;

class UserRepository extends ContentRepository {

    public function typedFindByUsername(string $type, string $username) : ?User {
        $result = $this->findOneBy([
            'type' => $type,
            'username' => $username,
        ]);

        return $result && $result instanceof User ? $result : null;
    }

}
