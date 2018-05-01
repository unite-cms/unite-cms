<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OrganizationMember
 *
 * @ORM\Table(name="organization_member")
 * @ORM\Entity()
 * @UniqueEntity(fields={"organization", "user"}, message="validation.user_already_member_of_organization")
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
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Choice(callback="allowedRoles", strict=true, multiple=true, multipleMessage="validation.invalid_selection")
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * @var Organization
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Organization", inversedBy="members")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $organization;

    /**
     * @var User
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Valid()
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
