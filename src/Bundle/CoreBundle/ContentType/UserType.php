<?php


namespace UniteCMS\CoreBundle\ContentType;

use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;

/**
 * @UniteAssert\UserType
 */
class UserType extends ContentType
{
    public function __construct(string $id, string $name, string $defaultPermission)
    {
        parent::__construct($id, $name, $defaultPermission);
        $this->permissions[ContentVoter::QUERY] = $defaultPermission;
        $this->permissions[ContentVoter::READ] = $defaultPermission;
        $this->id = $id;
    }
}
