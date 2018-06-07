<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 11:12
 */

namespace UniteCMS\RegistrationBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Form\Model\InvitationRegistrationModel;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;

class RegistrationModel extends InvitationRegistrationModel
{
    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     */
    private $organizationTitle;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/", message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\Organization::RESERVED_IDENTIFIERS")
     */
    private $organizationIdentifier;

    /**
     * @return string
     */
    public function getOrganizationTitle()
    {
        return $this->organizationTitle;
    }

    /**
     * @param string $organizationTitle
     * @return RegistrationModel
     */
    public function setOrganizationTitle(string $organizationTitle)
    {
        $this->organizationTitle = $organizationTitle;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrganizationIdentifier()
    {
        return $this->organizationIdentifier;
    }

    /**
     * @param string $organizationIdentifier
     * @return RegistrationModel
     */
    public function setOrganizationIdentifier(string $organizationIdentifier)
    {
        $this->organizationIdentifier = $organizationIdentifier;

        return $this;
    }
}