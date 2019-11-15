<?php


namespace UniteCMS\CoreBundle\ContentType;

use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;

/**
 * @UniteAssert\UserType
 */
class UserType extends ContentType
{
    public function __construct(string $id, string $name)
    {
        parent::__construct($id, $name);
        $this->permissions[ContentVoter::QUERY] = 'has_role("ROLE_ADMIN")';
        $this->permissions[ContentVoter::READ] = 'has_role("ROLE_ADMIN")';
        $this->id = $id;
    }
}
