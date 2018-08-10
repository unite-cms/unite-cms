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
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class Variants implements Fieldable
{
    /**
     * @var array
     */
    private $variantMetadata;

    /**
     * @var VariantsField[]|ArrayCollection
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

    public function __construct($variants, $identifier, $parent = null)
    {
        $this->fields = new ArrayCollection();
        $this->variantMetadata = [];
        $this->identifier = $identifier;
        $this->parent = $parent;

        foreach($variants as $variant) {

            $metaData = [
                'title' => $variant['title'],
                'identifier' => $variant['identifier'],
                'icon' => $variant['icon'] ?? '',
                'description' => $variant['description'] ?? '',
            ];

            foreach($variant['fields'] as $field) {
                $this->addField(new VariantsField($this, $variant['identifier'], $field['identifier'], $field['title'], $field['type'], new FieldableFieldSettings($field['settings'] ?? [])));
            }

            $this->variantMetadata[] = $metaData;
        }
    }

    /**
     * Returns all variant metadata.
     *
     * @return array
     */
    public function getVariantsMetadata()
    {
        return $this->variantMetadata;
    }

    /**
     * Returns all fields for a given variant identifier.
     *
     * @param string $variantIdentifier
     * @return array|VariantsField[]
     */
    public function getFieldsForVariant(string $variantIdentifier) {
        if(count(array_filter($this->variantMetadata, function($meta) use ($variantIdentifier) {
            return $meta['identifier'] === $variantIdentifier;
        })) == 1) {
            return $this->getFields()->filter(function(VariantsField $variant) use ($variantIdentifier) {
                return $variant->getVariantIdentifier() === $variantIdentifier;
            })->toArray();
        }
        return [];
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
        if(count($parts) < 2) {
            return null;
        }

        $variant_identifier = array_shift($parts);
        $field_identifier = array_shift($parts);

        $field = $this->getFieldsForVariant($variant_identifier)[$field_identifier];

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
}