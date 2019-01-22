<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 07.11.18
 * Time: 09:27
 */

namespace UniteCMS\VariantsFieldBundle\Model;

use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class VariantContent implements FieldableContent
{
    /**
     * @var Variant $variant
     */
    private $variant;

    /**
     * @var array $data
     */
    private $data;

    public function __construct(Variant $variant, array $data)
    {
        $this->variant = $variant;
        $this->data = $data;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return FieldableContent
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->getData();
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return null;
    }

    /**
     * @param $locale string|null
     * @return FieldableContent
     */
    public function setLocale($locale)
    {
        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->variant;
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableContent
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof Variant) {
            $this->variant = $entity;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool {
        return false;
    }
}