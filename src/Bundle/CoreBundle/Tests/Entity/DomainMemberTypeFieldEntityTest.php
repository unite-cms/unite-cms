<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;

class DomainMemberTypeFieldEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $field = new DomainMemberTypeField();
        $field
            ->setTitle('Title')
            ->setId(300);

        // test if id was set
        $this->assertEquals(300, $field->getId());

        // test if title is correct
        $this->assertEquals('Title', $field->__toString());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetEntityException()
    {
        $field = new DomainMemberTypeField();
        $field->setEntity(new Organization());
    }
}