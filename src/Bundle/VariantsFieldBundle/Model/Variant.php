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

    /**
     * @var array $data
     */
    private $data;

    public function __construct($fields, $identifier, $title, $parent = null, $data = [])
    {
        $this->fields = new ArrayCollection($fields);
        $this->identifier = $identifier;
        $this->title = $title;
        $this->parent = $parent;
        $this->data = $data;
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
        $this->fields->set($field->getIdentifier(), $field);

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
    public function getIdentifierPath($delimiter = '/', $include_root = true)
    {
        $path = '';

        if ($this->getParentEntity()) {
            $path = $this->getParentEntity()->getIdentifierPath($delimiter, $include_root);
        }

        if(!empty($path)) {
            $path .= $delimiter;
        }

        return $path.$this->getIdentifier();
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
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity(): Fieldable
    {
        return $this->getParentEntity() ? $this->parent->getRootEntity() : $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}