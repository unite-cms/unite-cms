<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;

class DomainMemberEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $domain_member = new DomainMember();

        $this->assertTrue($domain_member->isNew());

        $domain_member->setId(1);

        $this->assertEquals(1, $domain_member->getId());
        $this->assertFalse($domain_member->isNew());
    }
}