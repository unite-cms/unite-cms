<?php


namespace UniteCMS\CoreBundle\UserType;

use LogicException;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\Types\UserNameType;
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

    public function getUserNameField() : ContentTypeField {
        foreach($this->getFields() as $field) {
            if($field->getType() === UserNameType::getType()) {
                return $field;
            }
        }

        throw new LogicException('Every user type must have a user_name field.');
    }
}
