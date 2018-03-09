<?php

namespace UnitedCMS\CoreBundle\Entity;

/**
 * Defines fieldable content.
 */
interface FieldableContent
{

    /**
     * Set data
     *
     * @param array $data
     *
     * @return FieldableContent
     */
    public function setData(array $data);

    /**
     * Get data
     *
     * @return array
     */
    public function getData() : array;

    /**
     * @return string|null
     */
    public function getLocale();

    /**
     * @return Fieldable
     */
    public function getEntity();

    /**
     * @param Fieldable $entity
     *
     * @return FieldableContent
     */
    public function setEntity(Fieldable $entity);
}