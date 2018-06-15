<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * SettingType
 *
 * @ORM\Table(name="setting_type")
 * @ORM\Entity(repositoryClass="UniteCMS\CoreBundle\Repository\SettingTypeRepository")
 * @UniqueEntity(fields={"identifier", "domain"}, message="identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class SettingType implements Fieldable
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
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\SettingType::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Expose
     */
    private $description;

    /**
     * @var string
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_-]+$/", message="invalid_characters")
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     * @Expose
     */
    private $icon;

    /**
     * @var Domain
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="settingTypes")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $domain;

    /**
     * @var SettingTypeField[]
     * @Assert\Valid()
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\SettingTypeField>")
     * @Accessor(getter="getFields",setter="setFields")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\SettingTypeField", mappedBy="settingType", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $fields;

    /**
     * @var Setting[]|ArrayCollection
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\Setting>")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\Setting", mappedBy="settingType", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $settings;

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     * @ORM\Column(name="permissions", type="array", nullable=true)
     * @Expose
     */
    private $permissions;

    /**
     * @var array
     * @Assert\All({
     *     @Assert\Locale(),
     *     @Assert\NotBlank()
     * })
     * @ORM\Column(name="locales", type="array", nullable=true)
     * @Type("array<string>")
     * @Expose
     */
    private $locales;

    /**
     * @var int
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->locales = [];
        $this->addDefaultPermissions();
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    private function addDefaultPermissions()
    {
        $this->permissions[SettingVoter::VIEW] = 'true';
        $this->permissions[SettingVoter::UPDATE] = 'member.type == "editor"';
    }

    public function allowedPermissionKeys(): array
    {
        return SettingVoter::ENTITY_PERMISSIONS;
    }

    /**
     * Returns fieldTypes that are present in this settingType but not in $settingType.
     *
     * @param SettingType $settingType
     * @param bool $objects , return keys or objects
     *
     * @return SettingTypeField[]
     */
    public function getFieldTypesDiff(SettingType $settingType, $objects = false)
    {
        $keys = array_diff($this->getFields()->getKeys(), $settingType->getFields()->getKeys());

        if (!$objects) {
            return $keys;
        }

        $objects = [];
        foreach ($keys as $key) {
            $objects[] = $this->getFields()->get($key);
        }

        return $objects;
    }

    /**
     * This function sets all structure fields from the given entity and calls setFromEntity for all updated fields.
     *
     * @param SettingType $settingType
     * @return SettingType
     */
    public function setFromEntity(SettingType $settingType)
    {
        $this
            ->setTitle($settingType->getTitle())
            ->setIdentifier($settingType->getIdentifier())
            ->setWeight($settingType->getWeight())
            ->setIcon($settingType->getIcon())
            ->setDescription($settingType->getDescription())
            ->setLocales($settingType->getLocales())
            ->setPermissions($settingType->getPermissions());

        // Fields to delete
        foreach ($this->getFieldTypesDiff($settingType) as $field) {
            $this->getFields()->remove($field);
        }

        // Fields to add
        foreach (array_diff($settingType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->addField($settingType->getFields()->get($field));
        }

        // Fields to update
        foreach (array_intersect($settingType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->getFields()->get($field)->setFromEntity($settingType->getFields()->get($field));
        }

        return $this;
    }

    /**
     * After deserializing a content type, field weights must be initialized.
     *
     * @Serializer\PostDeserialize
     */
    public function initWeight() {
        $weight = 0;

        foreach($this->getFields() as $field) {
            $field->setWeight($weight);
            $weight++;
        }
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return SettingType
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
     * @return SettingType
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
     * @return SettingType
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
     * Set description
     *
     * @param string $description
     *
     * @return SettingType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set icon
     *
     * @param string $icon
     *
     * @return SettingType
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
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
     * @return SettingType
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        $domain->addSettingType($this);

        return $this;
    }

    /**
     * @return SettingTypeField[]|ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param SettingTypeField[]|ArrayCollection $fields
     *
     * @return SettingType
     */
    public function setFields($fields)
    {
        $this->fields->clear();
        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * @param FieldableField $field
     *
     * @return SettingType
     */
    public function addField(FieldableField $field)
    {
        if (!$field instanceof SettingTypeField) {
            throw new \InvalidArgumentException("'$field' is not a SettingTypeField.");
        }

        if (!$this->fields->containsKey($field->getIdentifier())) {
            $this->fields->set($field->getIdentifier(), $field);
            $field->setSettingType($this);

            if($field->getWeight() === null) {
                $field->setWeight($this->fields->count() - 1);
            }
        }

        return $this;
    }

    /**
     * @param Setting $setting
     * @return SettingType
     */
    public function addSetting(Setting $setting)
    {

        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
            $setting->setSettingType($this);
        }

        return $this;
    }

    /**
     * @param $settings
     * @return SettingType
     */
    public function setSettings($settings)
    {
        foreach ($settings as $setting) {
            $this->addSetting($setting);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Setting[]
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return Setting
     */
    public function getSetting($locale = null)
    {
        if ($this->getSettings()->count() > 0) {

            if (!$locale || empty($this->getLocales())) {
                return $this->getSettings()->first();
            }

            if (in_array($locale, $this->getLocales())) {
                $found = $this->getSettings()->filter(
                    function (Setting $setting) use ($locale) {
                        return $setting->getLocale() == $locale;
                    }
                );
                if (!$found->isEmpty()) {
                    return $found->first();
                }
            }
        }

        $setting = new Setting();
        $setting->setLocale($locale);
        $this->addSetting($setting);

        return $setting;
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
     * @return SettingType
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
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales ?? [];
    }

    /**
     * @param array $locales
     *
     * @return SettingType
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return SettingType
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity(): Fieldable
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierPath($delimiter = '/')
    {
        return $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentEntity()
    {
        return null;
    }
}

