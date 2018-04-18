<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class DomainMemberEntityTest extends ContainerAwareTestCase
{
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