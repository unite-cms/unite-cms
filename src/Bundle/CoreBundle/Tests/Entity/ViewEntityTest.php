<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ViewEntityTest extends DatabaseAwareTestCase
{

    public function testValidateView()
    {

        // Try to validate empty View.
        $view = new View();
        $view->setIdentifier('')->setTitle('');
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(5, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_view_type', $errors->get(3)->getMessage());

        $this->assertEquals('contentType', $errors->get(4)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(4)->getMessage());

        // Try to validate too long title, identifier, type
        $view
            ->setTitle($this->generateRandomUTF8String(256))
            ->setIdentifier($this->generateRandomMachineName(256))
            ->setType($this->generateRandomMachineName(256))
            ->setContentType(new ContentType())
            ->getContentType()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(4, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_view_type', $errors->get(3)->getMessage());

        // Try to validate invalid type
        $view
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier('identifier')
            ->setType('invalid');
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('type', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_view_type', $errors->get(0)->getMessage());

        // Try to validate invalid identifier
        $view
            ->setIdentifier('#')
            ->setType('table');

        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to validate invalid icon
        $view
            ->setIdentifier('any')
            ->setIcon($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $view->setIcon('# ');
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Test UniqueEntity Validation.
        $view->setIcon('any');
        $this->em->persist($view->getContentType()->getDomain()->getOrganization());
        $this->em->persist($view->getContentType()->getDomain());
        $this->em->persist($view);
        $this->em->flush($view);
        $this->em->refresh($view);

        $view2 = new View();
        $view2
            ->setTitle($view->getTitle())
            ->setIdentifier($view->getIdentifier())
            ->setContentType($view->getContentType())
            ->setType('table');
        $errors = $this->container->get('validator')->validate($view2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());
    }

    public function testReservedIdentifiers()
    {
        $reserved = View::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $view = new View();
        $view
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier(array_pop($reserved))
            ->setType('table')
            ->setContentType(new ContentType())
            ->getContentType()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }
}
