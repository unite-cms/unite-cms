<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Entity\Organization;

class SettingTypeFieldEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $field = new SettingTypeField();
        $field->setTitle('Title');
        $field->setIdentifier('test123');

        $this->assertEquals('Title', $field->__toString());
        $this->assertEquals('$."test123"', $field->getJsonExtractIdentifier());

        $field->setId(300);

        // test if id returns the same
        $this->assertEquals(300, $field->getId());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetEntityException()
    {
        $field = new SettingTypeField();
        $field->setEntity(new Organization());
    }
}