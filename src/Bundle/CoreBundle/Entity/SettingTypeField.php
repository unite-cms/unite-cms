<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;

/**
 * Field
 *
 * @ORM\Table(name="setting_type_field")
 * @ORM\Entity
 * @UniqueFieldableField(message="validation.identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class SettingTypeField implements FieldableField
{
    const RESERVED_IDENTIFIERS = ['type', 'locale'];

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
     * @ReservedWords(message="validation.reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\SettingTypeField::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @FieldType(message="validation.invalid_field_type")
     * @ORM\Column(name="type", type="string", length=255)
     * @Expose
     */
    private $type;

    /**
     * @var FieldableFieldSettings
     *
     * @ORM\Column(name="settings", type="object", nullable=true)
     * @ValidFieldSettings()
     * @Type("UniteCMS\CoreBundle\Field\FieldableFieldSettings")
     * @Assert\NotNull(message="validation.not_null")
     * @Expose
     */
    private $settings;

    /**
     * @var SettingType
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\SettingType", inversedBy="fields")
     */
    private $settingType;

    /**
     * @var int
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    public function __construct()
    {
        $this->settings = new FieldableFieldSettings();
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    /**
     * This function sets all structure fields from the given entity.
     *
     * @param SettingTypeField $field
     * @return SettingTypeField
     */
    public function setFromEntity(SettingTypeField $field)
    {
        $this
            ->setTitle($field->getTitle())
            ->setIdentifier($field->getIdentifier())
            ->setType($field->getType())
            ->setSettings($field->getSettings())
            ->setWeight($field->getWeight());

        return $this;
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return SettingTypeField
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
     * @return SettingTypeField
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
     * @return SettingTypeField
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
     * Returns the identifier, used for mysql's json_extract function.
     * @return string
     */
    public function getJsonExtractIdentifier()
    {
        return '$.'.$this->getIdentifier();
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return SettingTypeField
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set settings
     *
     * @param FieldableFieldSettings $settings
     *
     * @return SettingTypeField
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return FieldableFieldSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param SettingType $settingType
     *
     * @return SettingTypeField
     */
    public function setSettingType(SettingType $settingType)
    {
        $this->settingType = $settingType;
        $settingType->addField($this);

        return $this;
    }

    /**
     * @return SettingType
     */
    public function getSettingType()
    {
        return $this->settingType;
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
     * @return SettingTypeField
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->getSettingType();
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity)
    {
        if (!$entity instanceof SettingType) {
            throw new \InvalidArgumentException("'$entity' is not a SettingType.");
        }

        $this->setSettingType($entity);

        return $this;
    }
}

