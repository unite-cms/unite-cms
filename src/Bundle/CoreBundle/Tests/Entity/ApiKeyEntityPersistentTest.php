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
        $errors = static::$container->get('validator')->validate($apiClient);
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

        $errors = static::$container->get('validator')->validate($apiClient);
        $this->assertCount(2, $errors);

        $this->assertEquals('name', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        // Validate invalid token.
        $apiClient
            ->setToken('   '.$this->generateRandomUTF8String(150))
            ->setName($this->generateRandomUTF8String(255));

        $errors = static::$container->get('validator')->validate($apiClient);
        $this->assertCount(1, $errors);

        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        $apiClient->setToken('valid');

        // Validate valid token
        $errors = static::$container->get('validator')->validate($apiClient);
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

        $errors = static::$container->get('validator')->validate($apiClient2);
        $this->assertCount(1, $errors);
        $this->assertEquals('token', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.token_present', $errors->get(0)->getMessage());
    }

    public function testFindMethod() {

        $org1 = new Organization();
        $org1->setIdentifier('org1')->setTitle('Org 1');
        $org2 = new Organization();
        $org2->setIdentifier('org2')->setTitle('Org 2');

        $apiKey1 = new ApiKey();
        $apiKey1->setToken('token1')->setName('API Key 1')->setOrganization($org1);

        $apiKey1b = new ApiKey();
        $apiKey1b->setToken('token1b')->setName('API Key 1')->setOrganization($org1);

        $apiKey2 = new ApiKey();
        $apiKey2->setToken('token2')->setName('API Key 2')->setOrganization($org2);

        $this->em->persist($org1);
        $this->em->persist($org2);

        $this->em->persist($apiKey1);
        $this->em->persist($apiKey1b);
        $this->em->persist($apiKey2);

        $this->em->flush();

        $repo = $this->em->getRepository('UniteCMSCoreBundle:ApiKey');

        // Try to find with empty token
        $this->assertNull($repo->findOneByTokenAndOrganization('', 'org1'));

        // Try to find with empty organization
        $this->assertNull($repo->findOneByTokenAndOrganization('token1', ''));

        // Try to find with valid token and invalid organization
        $this->assertNull($repo->findOneByTokenAndOrganization('token1', 'org2'));
        $this->assertNull($repo->findOneByTokenAndOrganization('token1', 'foo'));

        // Try to find with invalid token and valid organization
        $this->assertNull($repo->findOneByTokenAndOrganization('foo', 'org1'));
        $this->assertNull($repo->findOneByTokenAndOrganization('token2', 'org1'));

        // Try to find with valid token and valid organization
        $this->assertEquals($apiKey1b, $repo->findOneByTokenAndOrganization('token1b', 'org1'));
    }

}
