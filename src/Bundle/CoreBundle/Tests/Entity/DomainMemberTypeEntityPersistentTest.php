<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DomainMemberTypeEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateDomainMemberType()
    {

        // Try to validate empty DomainMemberType.
        $domainMemberType = new DomainMemberType();
        $domainMemberType->setIdentifier('')->setTitle('')->setDescription('')->setIcon('');
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(3, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $this->assertEquals('domain', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());

        // Try to save a too long icon name or an icon name with special chars.
        $domainMemberType->setTitle('dmt1')->setIdentifier('dmt1')->setDomain(new Domain());
        $domainMemberType->setIcon($this->generateRandomMachineName(256));
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        $domainMemberType->setIcon('# ');
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        // Try to save invalid title.
        $domainMemberType->setIcon(null)->setTitle($this->generateRandomUTF8String(256));
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(1, $errors);
        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        // Try to save invalid identifier.
        $domainMemberType->setTitle($this->generateRandomUTF8String(255))->setIdentifier('X ');
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        $domainMemberType->setIdentifier($this->generateRandomMachineName(201));
        $errors = static::$container->get('validator')->validate($domainMemberType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        // There can only be one identifier per domain with the same identifier.
        $org1 = new Organization();
        $org1->setIdentifier('org1')->setTitle('Org 1');
        $org2 = new Organization();
        $org2->setIdentifier('org2')->setTitle('Org 2');
        $domain1 = new Domain();
        $domain1->setIdentifier('org1_domain1')->setTitle('Domain11');
        $domain2 = new Domain();
        $domain2->setIdentifier('org1_domain2')->setTitle('Domain12');
        $domain21 = new Domain();
        $domain21->setIdentifier('org2_domain1')->setTitle('Domain21');
        $domain22 = new Domain();
        $domain22->setIdentifier('org2_domain2')->setTitle('Domain22');
        $org1->addDomain($domain1)->addDomain($domain21);
        $org2->addDomain($domain2)->addDomain($domain22);
        $domainMemberType = new DomainMemberType();
        $domainMemberType->setIdentifier('org1_domain1_dmt1')->setTitle('org1_domain1_dmt1')->setDomain($domain1);
        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($domain21);
        $this->em->persist($domain22);
        $this->em->persist($domainMemberType);
        $this->em->flush($domainMemberType);
        $this->assertCount(0, static::$container->get('validator')->validate($domainMemberType));

        // DMT2 one the same domain with the same identifier should not be valid.
        $dmt2 = new DomainMemberType();
        $dmt2->setIdentifier('org1_domain1_dmt1')->setTitle('org1_domain1_dmt1')->setDomain($domain1);
        $this->assertCount(1, static::$container->get('validator')->validate($dmt2));

        $dmt2->setIdentifier('org1_domain1_dmt2');
        $this->assertCount(0, static::$container->get('validator')->validate($dmt2));

        $dmt2->setIdentifier('org1_domain1_dmt1')->setDomain($domain2);
        $this->assertCount(0, static::$container->get('validator')->validate($dmt2));

        $dmt2->setIdentifier('org1_domain1_dmt1')->setDomain($domain21);
        $this->assertCount(0, static::$container->get('validator')->validate($dmt2));

        $dmt2->setIdentifier('org1_domain1_dmt1')->setDomain($domain22);
        $this->assertCount(0, static::$container->get('validator')->validate($dmt2));

        // Test unique entity validation.
        $domainMemberType2 = new DomainMemberType();
        $domainMemberType2->setTitle($domainMemberType->getTitle())->setIdentifier($domainMemberType->getIdentifier())->setDomain(
            $domainMemberType->getDomain()
        );
        $errors = static::$container->get('validator')->validate($domainMemberType2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());
    }

    public function testDomainMemberTypeWeight()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setDomainMemberTypes([]);
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $dmt1 = new DomainMemberType();
        $dmt1->setIdentifier('dmt1')->setTitle('DMT1');
        $domain->addDomainMemberType($dmt1);

        $dmt2 = new DomainMemberType();
        $dmt2->setIdentifier('dmt2')->setTitle('DMT2');
        $domain->addDomainMemberType($dmt2);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($dmt1);
        $this->em->refresh($dmt2);
        $this->assertEquals(0, $dmt1->getWeight());
        $this->assertEquals(1, $dmt2->getWeight());

        // Reorder
        $reorderedDomain = new Domain();
        $reorderedDomain->setDomainMemberTypes([]);
        $reorderedDomain->setOrganization($org)->setTitle($domain->getTitle())->setIdentifier($domain->getIdentifier());

        $dmt1_clone = clone $dmt1;
        $dmt1_clone->setWeight(null);
        $dmt2_clone = clone $dmt2;
        $dmt2_clone->setWeight(null);
        $reorderedDomain->addDomainMemberType($dmt2_clone)->addDomainMemberType($dmt1_clone);
        $domain->setFromEntity($reorderedDomain);

        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertEquals(1, $dmt1->getWeight());
        $this->assertEquals(0, $dmt2->getWeight());
    }

    public function testReservedIdentifiers()
    {
        $reserved = DomainMemberType::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $ct = new DomainMemberType();
        $ct->setTitle('title')->setIdentifier(array_pop($reserved))->setDomain(new Domain());
        $errors = static::$container->get('validator')->validate($ct);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('reserved_identifier', $errors->get(0)->getMessageTemplate());
    }

    public function testFindByIdentifiers()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $this->em->persist($org);
        $this->em->flush($org);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);
        $this->em->persist($domain);
        $this->em->flush($domain);

        $domainMemberType = new DomainMemberType();
        $domainMemberType->setIdentifier('dmt1')->setTitle('Ct1')->setDomain($domain);
        $this->em->persist($domainMemberType);
        $this->em->flush($domainMemberType);

        // Try to find with invalid identifiers.
        $repo = $this->em->getRepository('UniteCMSCoreBundle:DomainMemberType');
        $this->assertNull($repo->findByIdentifiers('foo', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'dmt1'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'dmt1'));

        // Try to find with valid identifier.
        $this->assertEquals($domainMemberType, $repo->findByIdentifiers('org', 'domain', 'dmt1'));
    }

    public function testDomainMemberLabelProperty()
    {
        $ct = new DomainMemberType();
        $this->assertEquals('{accessor}', $ct->getDomainMemberLabel());
        $this->assertEquals('Foo', $ct->setDomainMemberLabel('Foo')->getDomainMemberLabel());
    }
}
