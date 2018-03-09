<?php

namespace UnitedCMS\CoreBundle\Tests\Entity;

use UnitedCMS\CoreBundle\Entity\ApiClient;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ApiClientEntityTest extends DatabaseAwareTestCase
{

    public function testValidateDomainApiClient()
    {

        // Validate empty ApiClient
        $apiClient = new ApiClient();
        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(4, $errors);

        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('roles', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('domain', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(3)->getMessage());

        // Validate too long name and token.
        $apiClient
            ->setToken($this->generateRandomMachineName(181))
            ->setName($this->generateRandomUTF8String(256));

        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(4, $errors);

        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        // Validate invalid token.
        $apiClient
            ->setToken('   ' . $this->generateRandomUTF8String(150))
            ->setName($this->generateRandomUTF8String(255));

        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(3, $errors);

        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Validate valid token name and domain
        $apiClient->setToken('azAZ09-_')->setDomain(new Domain());

        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(1, $errors);

        // Validate invalid roles
        $apiClient->setRoles(['ANY_UNKNOWN_ROLE']);
        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(1, $errors);
        $this->assertEquals('roles', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_selection', $errors->get(0)->getMessage());

        // Validate valid token
        $apiClient->setRoles([Domain::ROLE_ADMINISTRATOR]);
        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(0, $errors);

        $apiClient->getDomain()
            ->setTitle('Domain')
            ->setIdentifier('domain')
            ->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('Org');

        $this->em->persist($apiClient->getDomain()->getOrganization());
        $this->em->persist($apiClient->getDomain());
        $this->em->persist($apiClient);
        $this->em->flush($apiClient);

        // Validate apiClient with same token.
        $apiClient2 = new ApiClient();
        $apiClient2
            ->setRoles($apiClient->getRoles())
            ->setDomain($apiClient->getDomain())
            ->setName('Api Client 2')
            ->setToken($apiClient->getToken());

        $errors = $this->container->get('validator')->validate($apiClient2);
        $this->assertCount(1, $errors);
        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.token_present', $errors->get(0)->getMessage());

        // Validate apiClient with same name.
        $apiClient2->setName($apiClient->getName())->setToken('any_other_token');
        $errors = $this->container->get('validator')->validate($apiClient2);
        $this->assertCount(1, $errors);
        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.name_already_taken', $errors->get(0)->getMessage());
    }
}