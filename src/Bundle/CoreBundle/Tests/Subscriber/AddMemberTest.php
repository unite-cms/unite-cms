<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class AddMemberTest extends DatabaseAwareTestCase
{

    public function testAddDomainMemberNotInOrganization()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $user = new User();
        $user->setEmail('user@example.com')->setFirstname('User')->setLastname('User')->setPassword('XXX');
        $domain1 = new Domain();
        $domain1->setTitle('Domain')->setIdentifier('domain');
        $org1->addDomain($domain1);

        $this->em->persist($org1);
        $this->em->persist($domain1);
        $this->em->persist($user);
        $this->em->flush();

        $this->em->refresh($org1);
        $this->assertCount(0, $org1->getMembers());
        // Adding the user to domain1 should also add it to the organization.

        $domainMember1 = new DomainMember();
        $domainMember1->setDomain($domain1);
        $user->addDomain($domainMember1);
        $this->em->persist($domainMember1);
        $this->em->flush();

        $this->em->refresh($org1);
        $this->em->refresh($domain1);
        $this->assertCount(1, $domain1->getMembers());
        $this->assertCount(1, $org1->getMembers());
        $this->assertEquals($user, $org1->getMembers()->first()->getUser());
    }

    public function testAddDomainMemberAlreadyInOrganization()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $user = new User();
        $user->setEmail('user@example.com')->setFirstname('User')->setLastname('User')->setPassword('XXX');
        $domain1 = new Domain();
        $domain1->setTitle('Domain')->setIdentifier('domain');
        $org1->addDomain($domain1);

        $this->em->persist($org1);
        $this->em->persist($domain1);
        $this->em->persist($user);
        $this->em->flush();

        $orgMember = new OrganizationMember();
        $orgMember->setOrganization($org1);
        $user->addOrganization($orgMember);
        $this->em->flush();

        $this->em->refresh($org1);
        $this->assertCount(1, $org1->getMembers());

        // Adding the user to domain1 should do nothing to the domain.
        $domainMember1 = new DomainMember();
        $domainMember1->setDomain($domain1);
        $user->addDomain($domainMember1);
        $this->em->persist($domainMember1);
        $this->em->flush();

        $this->em->refresh($org1);
        $this->em->refresh($domain1);
        $this->assertCount(1, $domain1->getMembers());
        $this->assertCount(1, $org1->getMembers());
        $this->assertEquals($user, $org1->getMembers()->first()->getUser());
    }
}
