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
 * @UniqueEntity(fields={"domain", "accessor"}, message="validation.user_already_member_of_domain")
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
     * @Assert\Choice(callback="possibleDomains", strict=true, message="validation.domain_organization")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="members")
     */
    private $domain;

    /**
     * @var DomainAccessor
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="DomainAccessor", inversedBy="domains")
     */
    private $accessor;

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

    /**
     * Return possible domains for this domain member. This are the domains from the accessors organizations.
     * @return array
     */
    public function possibleDomains(): array
    {
        $domains = [];

        if(!$this->getAccessor()) {
            return $domains;
        }

        foreach($this->getAccessor()->getAccessibleOrganizations() as $organization) {
            foreach($organization->getDomains() as $domain) {
                $domains[] = $domain;
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
     * @return DomainAccessor
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @param DomainAccessor $accessor
     *
     * @return DomainMember
     */
    public function setAccessor(DomainAccessor $accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }
}
