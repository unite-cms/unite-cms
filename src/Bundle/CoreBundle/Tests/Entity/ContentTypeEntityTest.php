<?php

namespace UnitedCMS\CoreBundle\Tests\Entity;

use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ContentTypeEntityTest extends DatabaseAwareTestCase
{

    public function testValidateContentType()
    {

        // Try to validate empty ContentType.
        $contentType = new ContentType();
        $contentType->setIdentifier('')->setTitle('')->setDescription('')->setIcon('');
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(4, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('domain', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        // Try to save a too long icon name or an icon name with special chars.
        $contentType->setTitle('ct1')->setIdentifier('ct1')->setDomain(new Domain());
        $contentType->setIcon($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $contentType->setIcon('# ');
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to save invalid title.
        $contentType->setIcon(null)->setTitle($this->generateRandomUTF8String(256));
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        // Try to save invalid identifier.
        $contentType->setTitle($this->generateRandomUTF8String(255))->setIdentifier('X ');
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        $contentType->setIdentifier($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

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
        $contentType = new ContentType();
        $contentType->setIdentifier('org1_domain1_ct1')->setTitle('org1_domain1_ct1')->setDomain($domain1);
        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($domain21);
        $this->em->persist($domain22);
        $this->em->persist($contentType);
        $this->em->flush($contentType);
        $this->assertCount(0, $this->container->get('validator')->validate($contentType));

        // CT2 one the same domain with the same identifier should not be valid.
        $ct2 = new ContentType();
        $ct2->setIdentifier('org1_domain1_ct1')->setTitle('org1_domain1_ct1')->setDomain($domain1);
        $this->assertCount(1, $this->container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct2');
        $this->assertCount(0, $this->container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain2);
        $this->assertCount(0, $this->container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain21);
        $this->assertCount(0, $this->container->get('validator')->validate($ct2));

        $ct2->setIdentifier('org1_domain1_ct1')->setDomain($domain22);
        $this->assertCount(0, $this->container->get('validator')->validate($ct2));


        // Try to set invalid permissions.
        $contentType->setPermissions(['invalid' => ['invalid']]);
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('permissions', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_selection', $errors->get(0)->getMessage());

        // Test invalid view.
        $contentType->setPermissions([])->addView(new View());
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('views', $errors->get(0)->getPropertyPath());

        // Test ContentType without all view.
        $contentType->getViews()->clear();
        $errors = $this->container->get('validator')->validate($contentType);
        $this->assertCount(1, $errors);
        $this->assertEquals('views', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.missing_default_view', $errors->get(0)->getMessage());

        // Test unique entity validation.
        $contentType2 = new ContentType();
        $contentType2->setTitle($contentType->getTitle())->setIdentifier($contentType->getIdentifier())->setDomain(
            $contentType->getDomain()
        );
        $errors = $this->container->get('validator')->validate($contentType2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());

        // Deleting the "all" view from a contentType should not work.
        $ct3 = new ContentType();
        $ct3->setTitle('CT3')->setIdentifier('CT3')->setDomain($contentType->getDomain());
        $this->assertTrue($ct3->getViews()->containsKey(View::DEFAULT_VIEW_IDENTIFIER));

        $errors = $this->container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);

        $ct3->getViews()->remove(View::DEFAULT_VIEW_IDENTIFIER);
        $errors = $this->container->get('validator')->validate($ct3);
        $this->assertCount(1, $errors);
        $this->assertEquals('views', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.missing_default_view', $errors->get(0)->getMessage());

        // Try to delete other view
        $other_view = new View();
        $other_view->setIdentifier('other')->setTitle('Other')->setType('table');
        $ct3->setViews([$other_view]);
        $this->assertTrue($ct3->getViews()->containsKey(View::DEFAULT_VIEW_IDENTIFIER));
        $this->assertTrue($ct3->getViews()->containsKey('other'));

        $errors = $this->container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);
        $this->em->persist($ct3);
        $this->em->flush($ct3);
        $this->em->refresh($ct3);

        $ct3->getViews()->remove('other');
        $errors = $this->container->get('validator')->validate($ct3);
        $this->assertCount(0, $errors);
        $this->em->persist($ct3);
        $this->em->flush($ct3);
        $ct3 = $this->em->find('UnitedCMSCoreBundle:ContentType', $ct3->getId());
        $this->assertFalse($ct3->getViews()->containsKey('other'));
    }

    public function testContentTypeWeight()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $ct1 = new ContentType();
        $ct1->setIdentifier('ct1')->setTitle('CT1');
        $domain->addContentType($ct1);

        $ct2 = new ContentType();
        $ct2->setIdentifier('ct2')->setTitle('CT2');
        $domain->addContentType($ct2);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($ct1);
        $this->em->refresh($ct2);
        $this->assertEquals(0, $ct1->getWeight());
        $this->assertEquals(1, $ct2->getWeight());

        // Reorder
        $reorderedDomain = new Domain();
        $reorderedDomain->setOrganization($org)->setTitle($domain->getTitle())->setIdentifier($domain->getIdentifier());
        $reorderedDomain->addContentType(clone $ct2)->addContentType(clone $ct1);
        $domain->setFromEntity($reorderedDomain);

        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertEquals(1, $ct1->getWeight());
        $this->assertEquals(0, $ct2->getWeight());
    }

    public function testReservedIdentifiers()
    {
        $reserved = ContentType::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $ct = new ContentType();
        $ct->setTitle('title')->setIdentifier(array_pop($reserved))->setDomain(new Domain());
        $errors = $this->container->get('validator')->validate($ct);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }

    public function testFindByIdentifiers() {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $this->em->persist($org);
        $this->em->flush($org);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);
        $this->em->persist($domain);
        $this->em->flush($domain);

        $contentType = new ContentType();
        $contentType->setIdentifier('ct1')->setTitle('Ct1')->setDomain($domain);
        $this->em->persist($contentType);
        $this->em->flush($contentType);

        // Try to find with invalid identifiers.
        $repo = $this->em->getRepository('UnitedCMSCoreBundle:ContentType');
        $this->assertNull($repo->findByIdentifiers('foo', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('org', 'domain', 'luu'));
        $this->assertNull($repo->findByIdentifiers('foo', 'domain', 'ct1'));
        $this->assertNull($repo->findByIdentifiers('org', 'baa', 'ct1'));

        // Try to find with valid identifier.
        $this->assertEquals($contentType, $repo->findByIdentifiers('org', 'domain', 'ct1'));
    }

    public function testContentLabelProperty() {
        $ct = new ContentType();
        $this->assertEquals('{type} #{id}', $ct->getContentLabel());
        $this->assertEquals('Foo', $ct->setContentLabel('Foo')->getContentLabel());
    }
}