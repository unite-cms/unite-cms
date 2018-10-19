<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;

/**
 * Organization
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity()
 * @UniqueEntity(fields={"identifier"}, message="identifier_already_taken")
 */
class Organization
{

    const ROLE_USER = "ROLE_USER";
    const ROLE_ADMINISTRATOR = "ROLE_ADMINISTRATOR";

    const RESERVED_IDENTIFIERS = ['profile', 'api', 'app'];

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
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="200", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\Organization::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=200, unique=true)
     */
    private $identifier;

    /**
     * @var Domain[]
     * @Assert\Valid()
     * @Assert\Count(max="0", maxMessage="domains_must_be_empty", groups={"DELETE"})
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="organization")
     */
    private $domains;

    /**
     * @var OrganizationMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="OrganizationMember", mappedBy="organization", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $members;

    /**
     * @var ApiKey[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="ApiKey", mappedBy="organization", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $apiKeys;

    /**
     * @var Invitation[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="Invitation", mappedBy="organization", fetch="EXTRA_LAZY")
     */
    private $invites;

    public function __toString()
    {
        return ''.$this->title;
    }

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->apiKeys = new ArrayCollection();
        $this->invites = new ArrayCollection();
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
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param OrganizationMember[]|ArrayCollection $members
     *
     * @return Organization
     */
    public function setMembers($members)
    {
        $this->members->clear();
        foreach ($members as $member) {
            $this->addMember($member);
        }

        return $this;
    }

    /**
     * @param OrganizationMember $member
     *
     * @return Organization
     */
    public function addMember(OrganizationMember $member)
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setOrganization($this);
        }

        return $this;
    }

    /**
     * @return ApiKey[]|ArrayCollection
     */
    public function getApiKeys()
    {
        return $this->apiKeys;
    }

    /**
     * @param ApiKey[]|ArrayCollection $apiKeys
     *
     * @return Organization
     */
    public function setApiKeys($apiKeys)
    {
        $this->apiKeys->clear();
        foreach ($apiKeys as $apiKey) {
            $this->addApiKey($apiKey);
        }

        return $this;
    }

    /**
     * @param ApiKey $apiKey
     *
     * @return Organization
     */
    public function addApiKey(ApiKey $apiKey)
    {
        if (!$this->apiKeys->contains($apiKey)) {
            $this->apiKeys->add($apiKey);
            $apiKey->setOrganization($this);
        }

        return $this;
    }

    /**
     * @return Invitation[]|ArrayCollection
     */
    public function getInvites()
    {
        return $this->invites;
    }
}

