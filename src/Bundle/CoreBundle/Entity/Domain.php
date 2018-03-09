<?php

namespace UnitedCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UnitedCMS\CoreBundle\Validator\Constraints\ReservedWords;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity()
 * @UniqueEntity(fields={"identifier", "organization"}, message="validation.identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class Domain
{
    const ROLE_PUBLIC = "ROLE_PUBLIC";
    const ROLE_EDITOR = "ROLE_EDITOR";
    const ROLE_ADMINISTRATOR = "ROLE_ADMINISTRATOR";

    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete', 'user'];

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
     * @Expose
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * @ReservedWords(message="validation.reserved_identifier", reserved="UnitedCMS\CoreBundle\Entity\Domain::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var array
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\Column(name="roles", type="array")
     * @Assert\All({
     *     @Assert\NotBlank(message="validation.not_blank"),
     *     @Assert\Length(max = 200, maxMessage="validation.too_long"),
     *     @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * })
     * @Expose
     */
    private $roles;

    /**
     * @var Organization
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\ManyToOne(targetEntity="UnitedCMS\CoreBundle\Entity\Organization", inversedBy="domains")
     */
    private $organization;

    /**
     * @var ContentType[]
     * @Type("ArrayCollection<UnitedCMS\CoreBundle\Entity\ContentType>")
     * @Accessor(getter="getContentTypes",setter="setContentTypes")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\ContentType", mappedBy="domain", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $contentTypes;

    /**
     * @var SettingType[]
     * @Type("ArrayCollection<UnitedCMS\CoreBundle\Entity\SettingType>")
     * @Accessor(getter="getSettingTypes",setter="setSettingTypes")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\SettingType", mappedBy="domain", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $settingTypes;

    /**
     * @var DomainMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\DomainMember", mappedBy="domain", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $users;

    /**
     * @var DomainInvitation[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\DomainInvitation", mappedBy="domain", fetch="EXTRA_LAZY")
     */
    private $invites;

    /**
     * @var ApiClient[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\ApiClient", mappedBy="domain", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $apiClients;

    public function __toString()
    {
        return ''.$this->title;
    }

    public function __construct()
    {
        $this->roles = [Domain::ROLE_PUBLIC, Domain::ROLE_EDITOR, Domain::ROLE_ADMINISTRATOR];
        $this->users = new ArrayCollection();
        $this->contentTypes = new ArrayCollection();
        $this->settingTypes = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->apiClients = new ArrayCollection();
    }

    /**
     * Returns contentTypes that are present in this domain but not in $domain.
     *
     * @param Domain $domain
     * @param bool $objects , return keys or objects
     *
     * @return ContentType[]
     */
    public function getContentTypesDiff(Domain $domain, $objects = false)
    {
        $keys = array_diff($this->getContentTypes()->getKeys(), $domain->getContentTypes()->getKeys());

        if (!$objects) {
            return $keys;
        }

        $objects = [];
        foreach ($keys as $key) {
            $objects[] = $this->getContentTypes()->get($key);
        }

        return $objects;
    }

    /**
     * Returns settingTypes that are present in this domain but not in $domain.
     *
     * @param Domain $domain
     * @param bool $objects , return keys or objects
     *
     * @return \UnitedCMS\CoreBundle\Entity\SettingType[]
     */
    public function getSettingTypesDiff(Domain $domain, $objects = false)
    {
        $keys = array_diff($this->getSettingTypes()->getKeys(), $domain->getSettingTypes()->getKeys());

        if (!$objects) {
            return $keys;
        }

        $objects = [];
        foreach ($keys as $key) {
            $objects[] = $this->getSettingTypes()->get($key);
        }

        return $objects;
    }

    /**
     * This function sets all structure fields from the given entity and calls setFromEntity for all updated
     * contentTypes and settingTypes.
     *
     * @param Domain $domain
     * @return Domain
     */
    public function setFromEntity(Domain $domain)
    {
        $this
            ->setTitle($domain->getTitle())
            ->setIdentifier($domain->getIdentifier())
            ->setRoles($domain->getRoles());

        // ContentTypes to delete
        foreach ($this->getContentTypesDiff($domain) as $ct) {
            $this->getContentTypes()->remove($ct);
        }

        // ContentTypes to add
        foreach (array_diff($domain->getContentTypes()->getKeys(), $this->getContentTypes()->getKeys()) as $ct) {
            $this->addContentType($domain->getContentTypes()->get($ct));
        }

        // ContentTypes to update
        foreach (array_intersect($domain->getContentTypes()->getKeys(), $this->getContentTypes()->getKeys()) as $ct) {
            $this->getContentTypes()->get($ct)->setFromEntity($domain->getContentTypes()->get($ct));
        }

        // SettingTypes to delete
        foreach ($this->getSettingTypesDiff($domain) as $st) {
            $this->getContentTypes()->remove($st);
            unset($st);
        }

        // SettingTypes to add
        foreach (array_diff($domain->getSettingTypes()->getKeys(), $this->getSettingTypes()->getKeys()) as $st) {
            $this->addSettingType($domain->getSettingTypes()->get($st));
        }

        // SettingTypes to update
        foreach (array_intersect($domain->getSettingTypes()->getKeys(), $this->getSettingTypes()->getKeys()) as $st) {
            $this->getSettingTypes()->get($st)->setFromEntity($domain->getSettingTypes()->get($st));
        }

        return $this;
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return Domain
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
     * @return Domain
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
     * @return Domain
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
     * Set roles
     *
     * @param array $roles
     *
     * @return Domain
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Returns all available roles for this domain.
     *
     * @param bool $include_anonymous Should the anonymous role should be returned?
     * @return array|bool an array of roles formatted as form option input
     */
    public function getAvailableRolesAsOptions($include_anonymous = false)
    {
        $available_roles = array_flip($this->getRoles());

        if(!$include_anonymous) {
            unset($available_roles[Domain::ROLE_PUBLIC]);
        }

        foreach ($available_roles as $key => $available_role) {
            $available_roles[$key] = $key;
        }

        return $available_roles;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return Domain
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        $organization->addDomain($this);

        return $this;
    }

    /**
     * @return ContentType[]|ArrayCollection
     */
    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    /**
     * @param ContentType[] $contentTypes
     *
     * @return Domain
     */
    public function setContentTypes($contentTypes)
    {
        $this->contentTypes->clear();
        foreach ($contentTypes as $contentType) {
            $this->addContentType($contentType);
        }

        return $this;
    }

    /**
     * @param ContentType $contentType
     *
     * @return Domain
     */
    public function addContentType(ContentType $contentType)
    {
        if (!$this->contentTypes->contains($contentType)) {
            $this->contentTypes->set($contentType->getIdentifier(), $contentType);
            $contentType->setDomain($this);
            $contentType->setWeight($this->contentTypes->count() - 1);
        }

        return $this;
    }

    /**
     * @return SettingType[]|ArrayCollection
     */
    public function getSettingTypes()
    {
        return $this->settingTypes;
    }

    /**
     * @param SettingType[] $settingTypes
     *
     * @return Domain
     */
    public function setSettingTypes($settingTypes)
    {
        $this->settingTypes->clear();
        foreach ($settingTypes as $settingType) {
            $this->addSettingType($settingType);
        }

        return $this;
    }

    /**
     * @param SettingType $settingType
     *
     * @return Domain
     */
    public function addSettingType(SettingType $settingType)
    {
        if (!$this->settingTypes->contains($settingType)) {
            $this->settingTypes->set($settingType->getIdentifier(), $settingType);
            $settingType->setDomain($this);
            $settingType->setWeight($this->settingTypes->count() - 1);
        }

        return $this;
    }

    /**
     * @return DomainMember[]|ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param DomainMember[] $users
     *
     * @return Domain
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
     * @param DomainMember $user
     *
     * @return Domain
     */
    public function addUser(DomainMember $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setDomain($this);
        }

        return $this;
    }

    /**
     * @return DomainInvitation[]|ArrayCollection
     */
    public function getInvites()
    {
        return $this->invites;
    }

    /**
     * @return ApiClient[]|ArrayCollection
     */
    public function getApiClients()
    {
        return $this->apiClients;
    }
}

