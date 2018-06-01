<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints\OrganizationAdminPresent;

/**
 * OrganizationMember
 *
 * @ORM\Table(name="organization_member")
 * @ORM\Entity
 * @UniqueEntity(fields={"organization", "user"}, message="user_already_member_of_organization")
 */
class OrganizationMember
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Choice(callback="allowedRoles", strict=true, multiple=true, multipleMessage="invalid_selection")
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * @var Organization
     * @Assert\NotBlank(message="not_blank")
     * @OrganizationAdminPresent(groups={"UPDATE", "DELETE"}, message="no_organization_admins")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Organization", inversedBy="members")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $organization;

    /**
     * @var User
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Valid
     * @ORM\ManyToOne(targetEntity="User", inversedBy="organizations")
     */
    private $user;

    public function __construct()
    {
        $this->setRoles([Organization::ROLE_USER]);
    }

    public function allowedRoles(): array
    {
        return [Organization::ROLE_USER, Organization::ROLE_ADMINISTRATOR];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return OrganizationMember
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return OrganizationMember
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Allows to get a single admin or user role. This is helpful for form components, that cannot deal with arrays.
     *
     * @return string
     */
    public function getSingleRole() : string {
        if(in_array(Organization::ROLE_ADMINISTRATOR, $this->getRoles())) {
            return Organization::ROLE_ADMINISTRATOR;
        }

        return Organization::ROLE_USER;
    }

    /**
     * Allows to set a single admin or user role. This is helpful for form components, that cannot deal with arrays.
     *
     * @param string $role, Organization::ROLE_ADMINISTRATOR or Organization::ROLE_USER.
     * @return $this
     */
    public function setSingleRole(string $role) {
        if($role === Organization::ROLE_ADMINISTRATOR) {
            $this->setRoles([Organization::ROLE_ADMINISTRATOR]);
        } else {
            $this->setRoles([Organization::ROLE_USER]);
        }

        return $this;
    }

    /**
     * @return \UniteCMS\CoreBundle\Entity\Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return OrganizationMember
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return OrganizationMember
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
}
