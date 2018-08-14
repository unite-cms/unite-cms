<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use UniteCMS\CoreBundle\Field\FieldableValidation;

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
     * @return FieldableValidation[]
     */
    public function getValidations(): array;

    /**
     * Returns the identifier of this fieldable.
     *
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
     * Finds a (possible) nested field in this fieldable by a path ("title", "blocks/0/title" etc.). If $reduce_path is
     * set to true, the fieldable should remove all resolved parts from the path.
     * @param $path
     * @param bool $reduce_path
     * @return mixed
     */
    public function resolveIdentifierPath(&$path, $reduce_path = false);

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
