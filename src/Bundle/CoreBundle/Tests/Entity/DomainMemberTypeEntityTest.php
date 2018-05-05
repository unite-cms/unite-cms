<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;

use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\FieldableField;

class DomainMemberTypeEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $domainMemberType = new DomainMemberType();

        // test if parent returns null
        $this->assertEquals(null, $domainMemberType->getParentEntity());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFieldException()
    {
        $domainMemberType = new DomainMemberType();
        $test_field = $this->createMock(FieldableField::class);
        $domainMemberType->addField($test_field);
    }
}