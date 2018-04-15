<?php

namespace UniteCMS\CoreBundle\Entity;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

/**
 * Defines a fieldable entity.
 */
interface FieldableField
{

    /**
     * @return Fieldable
     */
    public function getEntity();

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns the identifier, used for mysql's json_extract function.
     * @return string
     */
    public function getJsonExtractIdentifier();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return null|FieldableFieldSettings
     */
    public function getSettings();
}
