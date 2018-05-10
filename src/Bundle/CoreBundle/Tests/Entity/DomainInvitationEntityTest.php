<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainInvitation;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class DomainInvitationEntityTest extends ContainerAwareTestCase
{
    public function testBasicOperations()
    {
        $invite = new DomainInvitation();

        $invite->setDomainMemberType(new DomainMemberType());
        $invite->setEmail('user1@example.com');
        $invite->setRoles([Domain::ROLE_EDITOR]);
        $invite->setToken('XXX')->setRequestedAt(new \DateTime());

        // test id change
        $invite->setId(20);
        $this->assertEquals(20, $invite->getId());
    }

    public function testClearToken()
    {
        $invite = new DomainInvitation();

        $invite->setDomainMemberType(new DomainMemberType());
        $invite->setEmail('user1@example.com');
        $invite->setRoles([Domain::ROLE_EDITOR]);
        $invite->setToken('XXX')->setRequestedAt(new \DateTime());

        // test token flush
        $invite->clearToken();
        $this->assertEquals(true, $invite->isExpired());
    }
}