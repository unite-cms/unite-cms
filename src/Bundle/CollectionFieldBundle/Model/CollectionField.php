<?php

namespace UniteCMS\CollectionFieldBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * We use this model only for validation!
 * @UniqueFieldableField(message="identifier_already_taken")
 */
class CollectionField implements FieldableField
{

    const RESERVED_IDENTIFIERS = ['id', 'created', 'updated', 'type', 'collection'];

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CollectionFieldBundle\Model\CollectionField::RESERVED_IDENTIFIERS")
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @FieldType(message="invalid_field_type")
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

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     */
    private $permissions;

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

        $this->permissions = [];
        $this->addDefaultPermissions();

        if(isset($field['permissions'])) {
            $this->setPermissions($field['permissions']);
        }
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
     * {@inheritdoc}
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
        return '$.'.$this->getIdentifierPath('[*].', false);
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
     * @return CollectionField
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
