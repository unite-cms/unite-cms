<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\AccessType;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SkipWhenEmpty;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * Field
 *
 * @ORM\Table(name="domain_member_type_field")
 * @ORM\Entity
 * @UniqueFieldableField(message="identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class DomainMemberTypeField implements FieldableField
{
    const RESERVED_IDENTIFIERS = ['id', 'created', 'updated', 'type'];

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
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\DomainMemberTypeField::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=200)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @FieldType(message="invalid_field_type")
     * @ORM\Column(name="type", type="string", length=255)
     * @Expose
     */
    private $type;

    /**
     * @var FieldableFieldSettings
     *
     * @ORM\Column(name="settings", type="object", nullable=true)
     * @ValidFieldSettings()
     * @Assert\NotNull(message="not_null")
     * @Type("UniteCMS\CoreBundle\Field\FieldableFieldSettings")
     * @Expose
     * @SkipWhenEmpty
     */
    private $settings;

    /**
     * @var DomainMemberType
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\DomainMemberType", inversedBy="fields")
     * @ORM\JoinColumn(name="domain_member_type_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $domainMemberType;

    /**
     * @var int
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     * @ORM\Column(name="permissions", type="array", nullable=true)
     * @AccessType("public_method")
     * @Expose
     */
    private $permissions;

    public function __construct()
    {
        $this->settings = new FieldableFieldSettings();
        $this->permissions = [];
        $this->addDefaultPermissions();
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    /**
     * This function sets all structure fields from the given entity.
     *
     * @param DomainMemberTypeField $field
     * @return DomainMemberTypeField
     */
    public function setFromEntity(DomainMemberTypeField $field)
    {
        $this
            ->setTitle($field->getTitle())
            ->setIdentifier($field->getIdentifier())
            ->setType($field->getType())
            ->setSettings($field->getSettings())
            ->setWeight($field->getWeight())
            ->setPermissions($field->getPermissions());

        return $this;
    }

    private function addDefaultPermissions()
    {
        $this->permissions[FieldableFieldVoter::LIST] = 'true';
        $this->permissions[FieldableFieldVoter::VIEW] = 'true';
        $this->permissions[FieldableFieldVoter::UPDATE] = 'true';
    }

    public function allowedPermissionKeys(): array
    {
        return array_merge(FieldableFieldVoter::ENTITY_PERMISSIONS, FieldableFieldVoter::BUNDLE_PERMISSIONS);
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return DomainMemberTypeField
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
     * @return DomainMemberTypeField
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
     * @return DomainMemberTypeField
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
     * Returns a path from the root fieldable to this fieldable. Identifier of the root fieldable should only be
     * included of include_root is set to true.
     *
     * @param string $delimiter
     * @param bool $include_root
     * @return string
     */
    public function getIdentifierPath($delimiter = '/', $include_root = true)
    {
        $path = '';

        if ($this->getEntity()) {
            $path = $this->getEntity()->getIdentifierPath($delimiter, $include_root);
        }

        if(!empty($path)) {
            $path .= $delimiter;
        }

        return $path.$this->getIdentifier();
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
     * @return DomainMemberTypeField
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
     * @return DomainMemberTypeField
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
     * @param DomainMemberType $domainMemberType
     *
     * @return DomainMemberTypeField
     */
    public function setDomainMemberType(DomainMemberType $domainMemberType)
    {
        $this->domainMemberType = $domainMemberType;
        $domainMemberType->addField($this);

        return $this;
    }

    /**
     * @return DomainMemberType
     */
    public function getDomainMemberType()
    {
        return $this->domainMemberType;
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
     * @return DomainMemberTypeField
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
        return $this->getDomainmemberType();
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity)
    {
        if (!$entity instanceof DomainMemberType) {
            throw new \InvalidArgumentException("'$entity' is not a DomainMemberType.");
        }

        $this->setDomainMemberType($entity);

        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions() : array
    {
        // Prevent null values. We always need an array response.
        if(empty($this->permissions)) {
            $this->addDefaultPermissions();
        }
        return $this->permissions;
    }


    /**
     * @param array $permissions
     *
     * @return DomainMemberTypeField
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
}

