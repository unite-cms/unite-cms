<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 07.11.18
 * Time: 09:27
 */

namespace UniteCMS\CollectionFieldBundle\Model;

use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class CollectionRow implements FieldableContent
{
    /**
     * @var Collection $collection
     */
    private $collection;

    /**
     * @var array $data
     */
    private $data;

    /**
     * @var FieldableContent $rootContent
     */
    private $rootContent;

    public function __construct(Collection $collection, array $data, FieldableContent $rootContent = null)
    {
        $this->collection = $collection;
        $this->data = $data;
        $this->rootContent = $rootContent;
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
     * @return CollectionRow
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
        return $this->collection;
    }

    /**
     * @param Fieldable $entity
     *
     * @return FieldableContent
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof Collection) {
            $this->collection = $entity;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool {
        return false;
    }

    /**
     * @return FieldableContent
     */
    public function getRootFieldableContent(): FieldableContent
    {
        return $this->rootContent;
    }
}