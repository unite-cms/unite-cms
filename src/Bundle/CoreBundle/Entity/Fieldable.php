<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines a fieldable entity.
 */
interface Fieldable
{

    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete'];

    /**
     * @return FieldableField[]|ArrayCollection
     */
    public function getFields();

    /**
     * @param ArrayCollection|FieldableField[] $fields
     *
     * @return Fieldable
     */
    public function setFields($fields);

    /**
     * @param FieldableField $field
     *
     * @return Fieldable
     */
    public function addField(FieldableField $field);

    /**
     * @return array
     */
    public function getLocales(): array;

    /**
     * Returns the identifier of this fieldable.
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns a path from the root fieldable to this fieldable. Root types (e.g. ContentType) should return only the
     * identifier.
     *
     * @param string $delimiter
     * @return string
     */
    public function getIdentifierPath($delimiter = '/');

    /**
     * Returns the direct parent of this fieldable. Root types (e.g. ContentType) should return null.
     * @return null|Fieldable
     */
    public function getParentEntity();

    /**
     * Returns the root fieldable that is managed by doctrine. Root types (e.g. ContentType) can return themselves.
     *
     * @return Fieldable
     */
    public function getRootEntity(): Fieldable;
}
