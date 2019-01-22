<?php

namespace UniteCMS\CoreBundle\Entity;

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
    public function getData(): array;

    /**
     * @return string|null
     */
    public function getLocale();

    /**
     * @param string|null $locale
     * @return Fieldable
     */
    public function setLocale($locale);

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

    /**
     * @return boolean
     */
    public function isNew(): bool;
}
