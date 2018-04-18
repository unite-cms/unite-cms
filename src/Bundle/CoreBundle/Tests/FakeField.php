<?php

namespace UniteCMS\CoreBundle\Tests;

use UniteCMS\CoreBundle\Entity\FieldableField;

class FakeField implements FieldableField
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return 'FakeField';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function  setEntity($entity)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return "test";
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return "test";
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonExtractIdentifier()
    {
        return "test";
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return "test";
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return "test";
    }
}