<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

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

    public function testApiClientSerialization()
    {
        $apiClient = new ApiClient();

        $org = new Organization();
        $org->setIdentifier('org1')->setTitle('Org 1');

        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain1')->setIdentifier('domain1');

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();

        $datetime = \DateTime::createFromFormat('Y-m-d H:m:s', '2018-01-01 10:10:10');

        $values = [
            'id' => NULL,
            'created' => $datetime,
            'name' => 'my_api_client',
            'token' => '12345',
            'roles' => [Domain::ROLE_ADMINISTRATOR],
            'domain' => $domain
        ];

        $apiClient
            ->setCreated($values['created'])
            ->setName($values['name'])
            ->setToken($values['token'])
            ->setRoles($values['roles'])
            ->setDomain($values['domain']);

        $this->em->persist($apiClient);
        $this->em->flush();

        # get saved serialized string
        $serialized = $apiClient->serialize();

        # add domain and set serialized values from array
        $values['id'] = $apiClient->getId();
        $apiClient->unserialize(serialize(array_values($values)));

        # get new serialized string
        $serialized_new = $apiClient->serialize();

        # should be the same
        $this->assertEquals($serialized, $serialized_new);

        $this->assertEquals(NULL, $apiClient->getSalt());

    }
}
