<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.08.18
 * Time: 15:48
 */

namespace UniteCMS\VariantsFieldBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;

class Variant implements Fieldable
{
    /**
     * @var VariantsField[]|ArrayCollection
     * @Assert\Valid()
     */
    private $fields;

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
     */
    private $title;

    /**
     * @var Fieldable $parent
     */
    private $parent;

    public function __construct($fields, $identifier, $title, $parent = null)
    {
        $this->fields = new ArrayCollection($fields);
        $this->identifier = $identifier;
        $this->title = $title;
        $this->parent = $parent;
    }

    /**
     * @return FieldableField[]|ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(FieldableField $field)
    {
        $this->fields->add($field);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocales(): array { return []; }

    /**
     * {@inheritdoc}
     */
    public function getValidations(): array { return []; }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier() { return $this->identifier; }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierPath($delimiter = '/')
    {
        $path = '';

        if ($this->getParentEntity()) {
            $path = $this->getParentEntity()->getIdentifierPath($delimiter).$delimiter;
        }

        return $path.$this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentEntity()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity(): Fieldable
    {
        return $this->getParentEntity() ? $this->parent->getRootEntity() : $this;
    }
}