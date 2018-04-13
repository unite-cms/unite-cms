<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;

/**
 * Organization
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity()
 * @UniqueEntity(fields={"identifier"}, message="validation.identifier_already_taken")
 */
class Organization
{

    const ROLE_USER = "ROLE_USER";
    const ROLE_ADMINISTRATOR = "ROLE_ADMINISTRATOR";

    const RESERVED_IDENTIFIERS = ["login", "profile"];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * @ReservedWords(message="validation.reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\Organization::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255, unique=true)
     */
    private $identifier;

    /**
     * @var Domain[]
     * @Assert\Valid()
     * @Assert\Count(max="0", maxMessage="validation.should_be_empty", groups={"DELETE"})
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\Domain", mappedBy="organization")
     */
    private $domains;

    /**
     * @var User[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\OrganizationMember", mappedBy="organization", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $users;

    public function __toString()
    {
        return ''.$this->title;
    }

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param int $id
     *
     * @return Organization
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set title
     *
     * @param string $title
     *
     * @return Organization
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return Organization
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return Domain[]|ArrayCollection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param Domain[] $domains
     *
     * @return Organization
     */
    public function setDomains($domains)
    {
        $this->domains->clear();
        foreach ($domains as $domain) {
            $this->addDomain($domain);
        }

        return $this;
    }

    /**
     * @param Domain $domain
     *
     * @return Organization
     */
    public function addDomain(Domain $domain)
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
            $domain->setOrganization($this);
        }

        return $this;
    }

    /**
     * @return OrganizationMember[]|ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param OrganizationMember[]|ArrayCollection $users
     *
     * @return Organization
     */
    public function setUsers($users)
    {
        $this->users->clear();
        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }

    /**
     * @param OrganizationMember $user
     *
     * @return Organization
     */
    public function addUser(OrganizationMember $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setOrganization($this);
        }

        return $this;
    }
}

