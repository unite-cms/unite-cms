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
     * Returns a path from the root fieldable to this fieldable. Identifier of the root fieldable should only be
     * included of include_root is set to true.
     *
     * @param string $delimiter
     * @param bool $include_root
     * @return string
     */
    public function getIdentifierPath($delimiter = '/', $include_root = true);

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

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return array
     */
    public function getPermissions() : array;
}
