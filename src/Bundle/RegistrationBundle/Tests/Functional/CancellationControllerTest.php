<?php

namespace UniteCMS\RegistrationBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Router;

class CancellationControllerTest extends WebTestCase {

    public function testAccessCancellationRoute() {

        $client = self::createClient();
        $client->request('GET', $client->getContainer()->get('router')->generate('unitecms_registration_cancellation_cancellation'));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect($client->getContainer()->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL)));
        
    }
}
