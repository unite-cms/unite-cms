<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;

/**
 * DomainMemberType
 *
 * @ORM\Table(name="domain_member_type")
 * @ORM\Entity(repositoryClass="UniteCMS\CoreBundle\Repository\DomainMemberTypeRepository")
 * @UniqueEntity(fields={"identifier", "domain"}, message="identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class DomainMemberType implements Fieldable
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
     * @Assert\Length(max="200", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\DomainMemberType::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=200)
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
     * @var string
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="content_label", type="string", length=255, nullable=true)
     * @Expose
     */
    private $domainMemberLabel = '{accessor}';

    /**
     * @var Domain
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="domainMemberTypes")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $domain;

    /**
     * @var DomainMemberTypeField[]
     * @Assert\Valid()
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\DomainMemberTypeField>")
     * @Accessor(getter="getFields",setter="setFields")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMemberTypeField", mappedBy="domainMemberType", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $fields;

    /**
     * @var int
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    /**
     * @var DomainMember[]|ArrayCollection
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\DomainMember>")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMember", mappedBy="domainMemberType", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $domainMembers;

    /**
     * @var Invitation[]
     * @ORM\OneToMany(targetEntity="Invitation", mappedBy="domainMemberType", fetch="EXTRA_LAZY")
     */
    private $invites;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->domainMembers = new ArrayCollection();
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    /**
     * Returns fieldTypes that are present in this domainMemberType but not in $domainMemberType.
     *
     * @param DomainMemberType $domainMemberType
     * @param bool $objects , return keys or objects
     *
     * @return DomainMemberTypeField[]
     */
    public function getFieldTypesDiff(DomainMemberType $domainMemberType, $objects = false)
    {
        $keys = array_diff($this->getFields()->getKeys(), $domainMemberType->getFields()->getKeys());

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
     * This function sets all structure fields from the given entity and calls setFromEntity for all updated
     * views and fields.
     *
     * @param DomainMemberType $domainMemberType
     * @return DomainMemberType
     */
    public function setFromEntity(DomainMemberType $domainMemberType)
    {
        $this
            ->setTitle($domainMemberType->getTitle())
            ->setIdentifier($domainMemberType->getIdentifier())
            ->setWeight($domainMemberType->getWeight())
            ->setIcon($domainMemberType->getIcon())
            ->setDomainMemberLabel($domainMemberType->getDomainMemberLabel())
            ->setDescription($domainMemberType->getDescription());

        // Fields to delete
        foreach ($this->getFieldTypesDiff($domainMemberType) as $field) {
            $this->getFields()->remove($field);
        }

        // Fields to add
        foreach (array_diff($domainMemberType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->addField($domainMemberType->getFields()->get($field));
        }

        // Fields to update
        foreach (array_intersect($domainMemberType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->getFields()->get($field)->setFromEntity($domainMemberType->getFields()->get($field));
        }

        return $this;
    }

    /**
     * After deserializing a domain member type, field weights must be initialized.
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
     * @return DomainMemberType
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
     * @return DomainMemberType
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
     * @return DomainMemberType
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
     * @return DomainMemberType
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
     * @return DomainMemberType
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
     * @param string $domainMemberLabel
     *
     * @return DomainMemberType
     */
    public function setDomainMemberLabel($domainMemberLabel)
    {
        $this->domainMemberLabel = $domainMemberLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomainMemberLabel()
    {
        return $this->domainMemberLabel;
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
     * @return DomainMemberType
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        $domain->addDomainMemberType($this);

        return $this;
    }

    /**
     * @return DomainMemberTypeField[]|ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param DomainMemberTypeField[]|ArrayCollection $fields
     *
     * @return DomainMemberType
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
     * @return DomainMemberType
     */
    public function addField(FieldableField $field)
    {
        if (!$field instanceof DomainMemberTypeField) {
            throw new \InvalidArgumentException("'$field' is not a DomainMemberTypeField.");
        }

        if (!$this->fields->containsKey($field->getIdentifier())) {
            $this->fields->set($field->getIdentifier(), $field);
            $field->setDomainMemberType($this);

            if($field->getWeight() === null) {
                $field->setWeight($this->fields->count() - 1);
            }
        }

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
     * @return DomainMemberType
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return ArrayCollection|DomainMember[]
     */
    public function getDomainMembers()
    {
        return $this->domainMembers;
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
    public function getIdentifierPath($delimiter = '/', $include_root = true)
    {
        return $include_root ? $this->getIdentifier() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function resolveIdentifierPath(&$path, $reduce_path = false)
    {
        $parts = explode('/', $path);
        if(count($parts) < 0) {
            return null;
        }

        $field_identifier = array_shift($parts);
        $field = $this->getFields()->get($field_identifier);

        if($reduce_path) {
            $path = join('/', $parts);
        }

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentEntity()
    {
        return null;
    }

    /**
     * @return array
     */
    public function getLocales(): array
    {
        return [];
    }

    /**
     * @return FieldableValidation[]
     */
    public function getValidations(): array
    {
        return [];
    }

    /**
     * @return Invitation[]|ArrayCollection
     */
    public function getInvites()
    {
        return $this->invites;
    }
}

