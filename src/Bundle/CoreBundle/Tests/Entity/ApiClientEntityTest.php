<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class ApiClientEntityTest extends ContainerAwareTestCase
{
    public function testBasicOperations()
    {
        $apiClient = new ApiClient();

        // test if salt returns null
        $this->assertEquals(NULL, $apiClient->getSalt());
    }

    public function testSerialization()
    {
        $apiClient = new ApiClient();

        $datetime = \DateTime::createFromFormat('Y-m-d H:m:s', '2018-01-01 10:10:10');

        $rp_id = new \ReflectionProperty($apiClient, 'id');
        $rp_id->setAccessible(true);
        $rp_id->setValue($apiClient, 123);

        $rp_created = new \ReflectionProperty($apiClient, 'created');
        $rp_created->setAccessible(true);
        $rp_created->setValue($apiClient, $datetime);

        $apiClient
            ->setName('my_api_client')
            ->setToken('12345')
            ->setRoles([Domain::ROLE_ADMINISTRATOR])
            ->setDomain(new Domain());

        // get saved serialized string
        $serialized = $apiClient->serialize();

        // add domain and set serialized values from array
        $apiClient->unserialize(
            serialize(
                [
                    123,
                    $datetime,
                    'my_api_client',
                    '12345',
                    [Domain::ROLE_ADMINISTRATOR],
                    new Domain()
                ]
            )
        );

        // get new serialized string
        $serialized_new = $apiClient->serialize();

        // should be the same
        $this->assertEquals($serialized, $serialized_new);
    }
}