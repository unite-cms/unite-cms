<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\ApiKey;

class ApiKeyEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $apiClient = new ApiKey();

        // test if salt returns null
        $this->assertEquals(null, $apiClient->getSalt());
    }

    public function testSerialization()
    {
        $apiKey = new ApiKey();

        $datetime = \DateTime::createFromFormat('Y-m-d H:m:s', '2018-01-01 10:10:10');

        $rp_id = new \ReflectionProperty($apiKey, 'id');
        $rp_id->setAccessible(true);
        $rp_id->setValue($apiKey, 123);

        $rp_created = new \ReflectionProperty($apiKey, 'created');
        $rp_created->setAccessible(true);
        $rp_created->setValue($apiKey, $datetime);

        $apiKey
            ->setName('my_api_key')
            ->setToken('12345');

        // get saved serialized string
        $serialized = $apiKey->serialize();

        // add domain and set serialized values from array
        $apiKey->unserialize(
            serialize(
                [
                    123,
                    $datetime,
                    'my_api_key',
                    '12345',
                ]
            )
        );

        // get new serialized string
        $serialized_new = $apiKey->serialize();

        // should be the same
        $this->assertEquals($serialized, $serialized_new);
    }
}