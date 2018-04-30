<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 30.04.18
 * Time: 15:11
 */

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user" = "User", "api_key" = "ApiKey"})
 */
abstract class Authenticated implements UserInterface, \Serializable
{
    /**
     * @var int
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var OrganizationMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\OrganizationMember", mappedBy="authenticated", cascade={"persist", "remove", "merge"})
     */
    protected $organizations;

    /**
     * @var DomainMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMember", mappedBy="authenticated", cascade={"persist", "remove", "merge"})
     */
    protected $domains;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->organizations = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return OrganizationMember[]|ArrayCollection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * @param OrganizationMember[] $oMembers
     *
     * @return Authenticated
     */
    public function setOrganizations($oMembers)
    {
        $this->organizations->clear();
        foreach ($oMembers as $oMember) {
            $this->addOrganization($oMember);
        }

        return $this;
    }

    /**
     * @param OrganizationMember $oMember
     *
     * @return Authenticated
     */
    public function addOrganization(OrganizationMember $oMember)
    {
        if (!$this->organizations->contains($oMember)) {
            $this->organizations->add($oMember);
            $oMember->setAuthenticated($this);
        }

        return $this;
    }

    /**
     * @return DomainMember[]|ArrayCollection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param DomainMember[] $dMembers
     *
     * @return Authenticated
     */
    public function setDomains($dMembers)
    {
        $this->domains->clear();
        foreach ($dMembers as $dMember) {
            $this->addDomain($dMember);
        }

        return $this;
    }

    /**
     * @param DomainMember $dMember
     *
     * @return Authenticated
     */
    public function addDomain(DomainMember $dMember)
    {
        if (!$this->domains->contains($dMember)) {
            $this->domains->add($dMember);
            $dMember->setAuthenticated($this);
        }

        return $this;
    }

    /**
     * Returns the roles of the user for a given domain.
     *
     * @param Domain $domain
     * @return Role[]|string[] The user roles for the domain
     */
    public function getDomainRoles(Domain $domain)
    {
        foreach ($this->getDomains() as $domainMember) {
            if (!empty($domain->getId()) && $domainMember->getDomain()->getId() === $domain->getId()) {
                return $domainMember->getRoles();
            }
        }

        return [Domain::ROLE_PUBLIC];
    }

    /**
     * Returns the roles of the user for a given organization.
     *
     * @param Organization $organization
     * @return Role[]|string[] The user roles for the organization
     */
    public function getOrganizationRoles(Organization $organization)
    {
        foreach ($this->getOrganizations() as $organizationMember) {
            if ($organizationMember->getOrganization() === $organization) {
                return $organizationMember->getRoles();
            }
        }

        return [];
    }
}