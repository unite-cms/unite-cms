<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.08.18
 * Time: 15:48
 */

namespace UniteCMS\VariantsFieldBundle\Model;

use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class Variant implements FieldableField
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $type;

    /**
     * @var FieldableFieldSettings
     */
    private $settings;

    /**
     * @var Variants $parent
     */
    private $parent;

    public function __construct(Variants $parent, string $identifier, string $title, string $type, FieldableFieldSettings $settings)
    {
        $this->identifier = $identifier;
        $this->title = $title;
        $this->type = $type;
        $this->settings = $settings;
        $this->parent = $parent;
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
     * Returns the identifier, used for mysql's json_extract function.
     * @return string
     */
    public function getJsonExtractIdentifier()
    {
        return '$.'.$this->getEntity()->getIdentifier().'.'.$this->getIdentifier();
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
}