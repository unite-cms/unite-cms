<?php


namespace UniteCMS\CoreBundle\ContentType;

use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;

/**
 * @UniteAssert\UserType
 */
class UserType extends ContentType
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->permissions[ContentVoter::QUERY] = 'is_granted("ROLE_ADMIN")';
        $this->permissions[ContentVoter::READ] = 'is_granted("ROLE_ADMIN")';
        $this->id = $id;
    }
}
