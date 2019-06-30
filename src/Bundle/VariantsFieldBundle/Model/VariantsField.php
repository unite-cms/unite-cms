<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.08.18
 * Time: 15:48
 */

namespace UniteCMS\VariantsFieldBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CollectionFieldBundle\Model\CollectionField;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * @UniqueFieldableField(message="identifier_already_taken")
 */
class VariantsField implements FieldableField
{
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
     */
    private $variantIdentifier;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
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
     * @var Variant $parent
     */
    private $parent;

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     */
    private $permissions;

    public function __construct(Variant $parent, string $variantIdentifier, string $identifier, string $title, string $type, array $permissions = [], FieldableFieldSettings $settings)
    {
        $this->variantIdentifier = $variantIdentifier;
        $this->identifier = $identifier;
        $this->title = $title;
        $this->type = $type;
        $this->settings = $settings;
        $this->parent = $parent;

        $this->permissions = [];
        $this->addDefaultPermissions();

        if(isset($permissions)) {
            $this->setPermissions($permissions);
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

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->parent;
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity)
    {
        $this->parent = $entity;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
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
     * @return string
     */
    public function getVariantIdentifier()
    {
        return $this->variantIdentifier;
    }

    /**
     * Returns the identifier, used for mysql's json_extract function.
     * @return string
     */
    public function getJsonExtractIdentifier()
    {
        return '$.'.$this->getIdentifierPath('.', false);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return null|FieldableFieldSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ''.$this->title;
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
     * @return VariantsField
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