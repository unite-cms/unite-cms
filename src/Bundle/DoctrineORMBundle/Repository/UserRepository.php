<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use UniteCMS\DoctrineORMBundle\Entity\User;

class UserRepository extends ContentRepository {

    public function findByUsername(string $username) : ?User {
        $result = $this->findOneBy([
            'username' => $username,
        ]);

        return $result && $result instanceof User ? $result : null;
    }

}
