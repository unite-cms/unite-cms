<?php

namespace UniteCMS\RegistrationBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class RegistrationControllerTest extends DatabaseAwareTestCase {

    protected static function bootKernel(array $options = array())
    {
        $options['environment'] = 'test_registration';
        return parent::bootKernel($options);
    }

    public function testSubmitRegistrationForm() {

        $org = new Organization();
        $org->setIdentifier('taken')->setTitle('Taken');
        $this->em->persist($org);
        $this->em->flush();

        $client = static::$container->get('test.client');
        $crawler = $client->request('GET', $client->getContainer()->get('router')->generate(
            'unitecms_registration_registration_registration',
            [],
            Router::ABSOLUTE_URL
        ));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[name]'] = 'This is me';
        $form['registration[email]'] = 'me@example.com';
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password1';
        $form['registration[organizationTitle]'] = 'New Organization';
        $form['registration[organizationIdentifier]'] = 'neworg';
        $crawler = $client->submit($form);

        // make sure, that we stay on the same page, because password was not correct.
        $this->assertCount(1, $crawler->filter('h2:contains("' . static::$container->get('translator')->trans('registration.registration.headline') . '")'));

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationIdentifier]'] = 'taken';
        $crawler = $client->submit($form);

        // make sure, that we stay on the same page, because organization identifier is already taken.
        $this->assertCount(1, $crawler->filter('h2:contains("' . static::$container->get('translator')->trans('registration.registration.headline') . '")'));

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationIdentifier]'] = 'new';
        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect(static::$container->get('router')->generate(
            'unitecms_core_domain_index',
            ['organization' => 'new'],
            Router::ABSOLUTE_URL
        )));
    }
}
