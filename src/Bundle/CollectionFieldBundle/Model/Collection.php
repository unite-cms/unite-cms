<?php

namespace UniteCMS\CollectionFieldBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;

/**
 * We use this model only for validation!
 */
class Collection implements Fieldable
{

    /**
     * @var CollectionField[]
     * @Assert\Valid()
     */
    private $fields;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var Fieldable $parent
     */
    private $parent;

    public function __construct($fields = [], $identifier, $parent = null)
    {
        $this->parent = $parent;
        $this->fields = new ArrayCollection();
        $this->setIdentifier($identifier);
        $this->setFields($fields);
    }

    /**
     * @return FieldableField[]|ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param ArrayCollection|FieldableField[] $fields
     *
     * @return Collection
     */
    public function setFields($fields)
    {
        foreach ($fields as $field) {
            if ($field instanceof CollectionField) {
                $this->addField($field);
            } elseif (is_array($field)) {
                $this->addField(new CollectionField($field));
            }
        }

        return $this;
    }

    /**
     * @param FieldableField $field
     *
     * @return Fieldable
     */
    public function addField(FieldableField $field)
    {
        if (!$field instanceof CollectionField) {
            throw new \InvalidArgumentException("'$field' is not a CollectionField.");
        }

        if (!$this->fields->containsKey($field->getIdentifier())) {
            $this->fields->set($field->getIdentifier(), $field);
            $field->setEntity($this);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLocales(): array
    {
        return [];
    }

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
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return Collection
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity(): Fieldable
    {
        return $this->getParentEntity() ? $this->parent->getRootEntity() : $this;
    }
}
