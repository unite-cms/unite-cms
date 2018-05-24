<?php

namespace UniteCMS\RegistrationBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase {

    public function testAccessRegistrationRoute() {

        $client = self::createClient();
        $client->request('GET', $client->getContainer()->get('router')->generate('unitecms_registration_registration_registration'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
    }
}
