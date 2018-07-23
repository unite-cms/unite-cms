<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class InvitationEntityTest extends ContainerAwareTestCase
{
    public function testBasicOperations()
    {
        $type = new DomainMemberType();
        $org = new Organization();
        $invite = new Invitation();

        $invite->setDomainMemberType($type)->setOrganization($org);
        $invite->setEmail('user1@example.com');
        $invite->setToken('XXX')->setRequestedAt(new \DateTime());

        // test id change
        $invite->setId(20);
        $this->assertEquals(20, $invite->getId());
        $this->assertEquals('user1@example.com', $invite->getEmail());
        $this->assertEquals('XXX', $invite->getToken());
        $this->assertEquals($type, $invite->getDomainMemberType());
        $this->assertEquals($org, $invite->getOrganization());
    }

    public function testClearToken()
    {
        $invite = new Invitation();
        $invite->setToken('XXX')->setRequestedAt(new \DateTime());

        // test token flush
        $invite->clearToken();
        $this->assertEquals(true, $invite->isExpired());
    }
}