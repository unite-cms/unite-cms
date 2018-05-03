<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ApiKeyEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateDomainApiClient()
    {

        // Validate empty ApiClient
        $apiClient = new ApiKey();
        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(3, $errors);

        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('organization', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        // Validate too long name and token.
        $apiClient
            ->setOrganization(new Organization())
            ->setToken($this->generateRandomMachineName(181))
            ->setName($this->generateRandomUTF8String(256));
        $apiClient->getOrganization()->setTitle('Org')->setIdentifier('org');

        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(2, $errors);

        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        // Validate invalid token.
        $apiClient
            ->setToken('   '.$this->generateRandomUTF8String(150))
            ->setName($this->generateRandomUTF8String(255));

        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(1, $errors);

        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        $apiClient->setToken('valid');

        // Validate valid token
        $errors = $this->container->get('validator')->validate($apiClient);
        $this->assertCount(0, $errors);

        $this->em->persist($apiClient->getOrganization());
        $this->em->persist($apiClient);
        $this->em->flush($apiClient);

        // Validate apiClient with same token.
        $apiClient2 = new ApiKey();
        $apiClient2
            ->setOrganization($apiClient->getOrganization())
            ->setName('Api Client 2')
            ->setToken($apiClient->getToken());

        $errors = $this->container->get('validator')->validate($apiClient2);
        $this->assertCount(1, $errors);
        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.token_present', $errors->get(0)->getMessage());
    }

}
