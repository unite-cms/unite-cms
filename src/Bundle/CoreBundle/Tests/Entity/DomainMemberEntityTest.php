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
        $domain_member->setId(1);
        $this->assertEquals(1, $domain_member->getId());
    }

    public function testRoles()
    {
        $user1MemberDomain1 = new DomainMember();

        $this->assertCount(
            0,
            $user1MemberDomain1->allowedRoles()
        );

        $domain1 = new Domain();
        $domain1->setRoles([Domain::ROLE_EDITOR, Domain::ROLE_ADMINISTRATOR]);

        $user1MemberDomain1->setDomain($domain1);
        $this->assertCount(
            2,
            $user1MemberDomain1->allowedRoles()
        );
    }
}