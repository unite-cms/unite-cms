<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields={"identifier", "organization"}, message="identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class Domain
{
    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete', 'user', 'api', 'app'];

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
     * @Expose
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="200", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\Domain::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=200)
     * @Expose
     */
    private $identifier;

    /**
     * @var Organization
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Organization", inversedBy="domains")
     */
    private $organization;

    /**
     * @var ContentType[]
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\ContentType>")
     * @Accessor(getter="getOrderedContentTypes",setter="setContentTypes")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\ContentType", mappedBy="domain", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $contentTypes;

    /**
     * @var SettingType[]
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\SettingType>")
     * @Accessor(getter="getOrderedSettingTypes",setter="setSettingTypes")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\SettingType", mappedBy="domain", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $settingTypes;

    /**
     * @var DomainMemberType[]
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\DomainMemberType>")
     * @Accessor(getter="getOrderedDomainMemberTypes",setter="setDomainMemberTypes")
     * @Assert\Count(min="1", minMessage="member_type_required")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMemberType", mappedBy="domain", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $domainMemberTypes;

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     * @ORM\Column(name="permissions", type="array", nullable=true)
     * @Expose
     */
    private $permissions;

    /**
     * @var DomainMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMember", mappedBy="domain", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY", orphanRemoval=true)
     */
    private $members;

    /**
     * This holds the json config for this domain. This config was not generated automatically from this domain, but is
     * loaded from the filesystem. This allows us to define variables on the domain config, that get substituted when
     * creating a domain entity from the config. This config also does not get saved to the database.
     * @var string
     */
    private $config;

    /**
     * When updating a domain, this variable can hold the previous identifier during the current request.
     * @var string
     */
    private $previousIdentifier = null;

    /**
     * If the config has changed, this variable is set to true to force an update.
     *
     * @var bool
     */
    private $configChanged = false;

    public function __toString()
    {
        return ''.$this->title;
    }

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->contentTypes = new ArrayCollection();
        $this->settingTypes = new ArrayCollection();
        $this->domainMemberTypes = new ArrayCollection();

        $this->addDefaultMemberTypes();
        $this->addDefaultPermissions();
    }

    /**
     * @ORM\PreFlush
     * @param PreFlushEventArgs $args
     */
    public function forceUpdateIfConfigChanged(PreFlushEventArgs $args) {
        if($this->configChanged) {
            $args->getEntityManager()->getUnitOfWork()->scheduleForUpdate($this);
        }
    }

    private function addDefaultMemberTypes()
    {
        $editor = new DomainMemberType();
        $editor
            ->setTitle('Editor')
            ->setIdentifier('editor');

        $viewer = new DomainMemberType();
        $viewer
            ->setTitle('Viewer')
            ->setIdentifier('viewer');

        $this->addDomainMemberType($editor);
        $this->addDomainMemberType($viewer);
    }

    private function addDefaultPermissions()
    {
        $this->permissions[DomainVoter::VIEW] = 'true';
        $this->permissions[DomainVoter::UPDATE] = 'false';
    }

    public function allowedPermissionKeys(): array
    {
        return DomainVoter::ENTITY_PERMISSIONS;
    }

    /**
     *  checks if current domain has content types or settings types and returns true if so
     *
     * @return boolean
     */
    public function hasContentOrSettingTypes()
    {
        if ($this->getContentTypes()->count() > 0 or $this->getSettingTypes()->count() > 0) {
            return true;
        }

        return false;
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
     * @return \UniteCMS\CoreBundle\Entity\SettingType[]
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
     * Returns domainMemberTypes that are present in this domain but not in $domain.
     *
     * @param Domain $domain
     * @param bool $objects , return keys or objects
     *
     * @return \UniteCMS\CoreBundle\Entity\DomainMemberType[]
     */
    public function getDomainMemberTypesDiff(Domain $domain, $objects = false)
    {
        $keys = array_diff($this->getDomainMemberTypes()->getKeys(), $domain->getDomainMemberTypes()->getKeys());

        if (!$objects) {
            return $keys;
        }

        $objects = [];
        foreach ($keys as $key) {
            $objects[] = $this->getDomainMemberTypes()->get($key);
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
            ->setPermissions($domain->getPermissions())
            ->setConfig($domain->getConfig());

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

        // Update weight of all content types.
        foreach($domain->getContentTypes()->getKeys() as $weight => $key) {
            $this->getContentTypes()->get($key)->setWeight($weight);
        }


        // SettingTypes to delete
        foreach ($this->getSettingTypesDiff($domain) as $st) {
            $this->getSettingTypes()->remove($st);
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

        // Update weight of all setting types.
        foreach($domain->getSettingTypes()->getKeys() as $weight => $key) {
            $this->getSettingTypes()->get($key)->setWeight($weight);
        }


        // DomainMemberTypes to delete
        foreach ($this->getDomainMemberTypesDiff($domain) as $dmt) {
            $this->getDomainMemberTypes()->remove($dmt);
        }

        // DomainMemberTypes to add
        foreach (array_diff($domain->getDomainMemberTypes()->getKeys(), $this->getDomainMemberTypes()->getKeys()) as $dmt) {
            $this->addDomainMemberType($domain->getDomainMemberTypes()->get($dmt));
        }

        // DomainMemberTypes to update
        foreach (array_intersect($domain->getDomainMemberTypes()->getKeys(), $this->getDomainMemberTypes()->getKeys()) as $dmt) {
            $this->getDomainMemberTypes()->get($dmt)->setFromEntity($domain->getDomainMemberTypes()->get($dmt));
        }

        // Update weight of all domain member types.
        foreach($domain->getDomainMemberTypes()->getKeys() as $weight => $key) {
            $this->getDomainMemberTypes()->get($key)->setWeight($weight);
        }

        return $this;
    }

    /**
     * After deserializing a domain, content type and setting type weights must be initialized.
     *
     * @Serializer\PostDeserialize
     */
    public function initWeight() {

       $weight = 0;

       foreach($this->getContentTypes() as $contentType) {
           $contentType->setWeight($weight);
           $weight++;
       }

        $weight = 0;

        foreach($this->getSettingTypes() as $settingType) {
            $settingType->setWeight($weight);
            $weight++;
        }

        $weight = 0;

        foreach($this->getDomainMemberTypes() as $domainMemberType) {
            $domainMemberType->setWeight($weight);
            $weight++;
        }
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
        if(!empty($this->identifier) && $identifier !== $this->identifier) {
            $this->setPreviousIdentifier($this->identifier);
        }

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
     * @return ContentType[]|ArrayCollection
     */
    public function getOrderedContentTypes()
    {
        $iterator = $this->contentTypes->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
        });
        return new ArrayCollection(iterator_to_array($iterator));
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

            if($contentType->getWeight() === null) {
                $contentType->setWeight($this->contentTypes->count() - 1);
            }
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
     * @return SettingType[]|ArrayCollection
     */
    public function getOrderedSettingTypes()
    {
        $iterator = $this->settingTypes->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
        });
        return new ArrayCollection(iterator_to_array($iterator));
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

            if($settingType->getWeight() === null) {
                $settingType->setWeight($this->settingTypes->count() - 1);
            }
        }

        return $this;
    }

    /**
     * @return DomainMemberType[]|ArrayCollection
     */
    public function getDomainMemberTypes()
    {
        return $this->domainMemberTypes;
    }

    /**
     * @return DomainMemberType[]|ArrayCollection
     */
    public function getOrderedDomainMemberTypes()
    {
        $iterator = $this->domainMemberTypes->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
        });
        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * @param DomainMemberType[] $domainMemberTypes
     *
     * @return Domain
     */
    public function setDomainMemberTypes($domainMemberTypes)
    {
        $this->domainMemberTypes->clear();
        foreach ($domainMemberTypes as $domainMemberType) {
            $this->addDomainMemberType($domainMemberType);
        }

        return $this;
    }

    /**
     * @param DomainMemberType $domainMemberType
     *
     * @return Domain
     */
    public function addDomainMemberType(DomainMemberType $domainMemberType)
    {
        if (!$this->domainMemberTypes->contains($domainMemberType)) {
            $this->domainMemberTypes->set($domainMemberType->getIdentifier(), $domainMemberType);
            $domainMemberType->setDomain($this);

            if($domainMemberType->getWeight() === null) {
                $domainMemberType->setWeight($this->domainMemberTypes->count() - 1);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param array $permissions
     *
     * @return Domain
     */
    public function setPermissions($permissions)
    {
        $this->permissions = [];
        $this->addDefaultPermissions();

        foreach ($permissions as $attribute => $expression) {
            $this->addPermission($attribute, $expression);
        }

        return $this;
    }

    public function addPermission($attribute, string $expression)
    {
        $this->permissions[$attribute] = $expression;
    }

    /**
     * @return DomainMember[]|ArrayCollection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param DomainMember[] $members
     *
     * @return Domain
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
     * @param DomainMember $member
     *
     * @return Domain
     */
    public function addMember(DomainMember $member)
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setDomain($this);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->config ?? '';
    }

    /**
     * @param string $config
     * @return Domain
     */
    public function setConfig(string $config) {
        if(!empty($this->config) && $this->config !== $config) {
            $this->setConfigChanged();
        }
        $this->config = $config;
        return $this;
    }

    /**
     * Set the config of ths domain to changed. This is calculated automatically on setConfig. However, you can override
     * it with this method if you want to force or prevent a change event.
     * @param bool $changed
     */
    public function setConfigChanged($changed = true) {
        $this->configChanged = $changed;
    }

    /**
     * @return string
     */
    public function getPreviousIdentifier()
    {
        return $this->previousIdentifier;
    }

    /**
     * @param string $previousIdentifier
     * @return Domain
     */
    public function setPreviousIdentifier(string $previousIdentifier)
    {
        $this->previousIdentifier = $previousIdentifier;
        return $this;
    }
}

