<?php

namespace UniteCMS\CollectionFieldBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;

/**
 * We use this model only for validation!
 * @UniqueFieldableField(message="validation.identifier_already_taken")
 */
class CollectionField implements FieldableField
{

    const RESERVED_IDENTIFIERS = ['id', 'created', 'updated', 'type', 'collection'];

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * @ReservedWords(message="validation.reserved_identifier", reserved="UniteCMS\CollectionFieldBundle\Model\CollectionField::RESERVED_IDENTIFIERS")
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @FieldType(message="validation.invalid_field_type")
     */
    private $type;

    /**
     * @var FieldableFieldSettings
     *
     * @ValidFieldSettings()
     */
    private $settings;

    /**
     * @var Collection $collection
     */
    private $collection;

    public function __construct($field)
    {
        if (isset($field['title'])) {
            $this->setTitle($field['title']);
        }

        if (isset($field['identifier'])) {
            $this->setIdentifier($field['identifier']);
        }

        if (isset($field['type'])) {
            $this->setType($field['type']);
        }

        $this->setSettings(new FieldableFieldSettings(isset($field['settings']) ? $field['settings'] : []));
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return CollectionField
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
     * @return CollectionField
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
     * Set type
     *
     * @param string $type
     *
     * @return CollectionField
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
     * @return CollectionField
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
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->collection;
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity)
    {
        $this->collection = $entity;

        return $this;
    }

    /**
     * Returns the identifier, used for mysql's json_extract function.
     *
     * @return string
     */
    public function getJsonExtractIdentifier()
    {
        $pathParts = explode('/', $this->getEntity()->getIdentifierPath());

        // remove root entity path.
        array_shift($pathParts);

        // add this identifier.
        $pathParts[] = $this->getIdentifier();

        return '$.'.join('[*].', $pathParts);
    }
}
