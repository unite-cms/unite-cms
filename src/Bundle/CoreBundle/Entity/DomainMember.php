<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DomainMember
 *
 * @ORM\Table(name="domain_member")
 * @ORM\Entity()
 * @UniqueEntity(fields={"domain", "user"}, message="validation.user_already_member_of_domain")
 */
class DomainMember
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
     * @var Domain
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Choice(callback="allowedDomains", strict=true, message="validation.domain_organization")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="users")
     */
    private $domain;

    /**
     * @var User
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\User", inversedBy="domains")
     */
    private $user;

    public function __construct()
    {
        $this->roles = [Domain::ROLE_EDITOR];
    }

    public function allowedRoles(): array
    {
        if ($this->getDomain()) {
            return $this->getDomain()->getAvailableRolesAsOptions();
        }

        return [];
    }

    public function allowedDomains(): array
    {
        $domains = [];
        if ($this->getUser()) {
            foreach ($this->getUser()->getOrganizations() as $organizationMember) {
                $domains = array_merge($domains, $organizationMember->getOrganization()->getDomains()->toArray());
            }
        }
        return $domains;
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
     * @return DomainMember
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
     * @return DomainMember
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     *
     * @return DomainMember
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;

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
     * @return DomainMember
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
}
