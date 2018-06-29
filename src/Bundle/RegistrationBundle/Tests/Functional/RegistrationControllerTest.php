<?php

namespace UniteCMS\RegistrationBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

class RegistrationControllerTest extends DatabaseAwareTestCase {

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
    }

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

        $crawler = $this->client->request('GET', $this->client->getContainer()->get('router')->generate(
            'unitecms_registration_registration_registration',
            [],
            Router::ABSOLUTE_URL
        ));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[name]'] = 'This is me';
        $form['registration[email]'] = 'me@example.com';
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password1';
        $form['registration[organizationTitle]'] = 'New Organization';
        $form['registration[organizationIdentifier]'] = 'neworg';
        $crawler = $this->client->submit($form);

        // make sure, that we stay on the same page, because password was not correct.
        $this->assertCount(1, $crawler->filter('h2:contains("' . static::$container->get('translator')->trans('registration.registration.headline') . '")'));

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationIdentifier]'] = 'taken';
        $crawler = $this->client->submit($form);

        // make sure, that we stay on the same page, because organization identifier is already taken.
        $this->assertCount(1, $crawler->filter('h2:contains("' . static::$container->get('translator')->trans('registration.registration.headline') . '")'));

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationIdentifier]'] = 'new';
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate(
            'unitecms_core_domain_index',
            ['organization' => 'new'],
            Router::ABSOLUTE_URL
        )));
    }

    private function login(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testRegistrationRouteLoggedIn() {

        $org = new Organization();
        $org->setTitle('registration access check')->setIdentifier('test3');

        $this->em->persist($org);
        $this->em->flush();
        $this->em->refresh($org);

        $user = new User();
        $user->setEmail('domain_editor@example.com')
             ->setName('Domain Editor')
             ->setRoles([User::ROLE_USER])
             ->setPassword('XXX');

        $orgMember = new OrganizationMember();
        $orgMember->setRoles([Organization::ROLE_USER])->setOrganization($org);

        $user->addOrganization($orgMember);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->refresh($user);

        $this->login($user);

        $this->client->request('GET', $this->client->getContainer()->get('router')->generate(
            'unitecms_registration_registration_registration',
            [],
            Router::ABSOLUTE_URL
        ));

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate(
            'unitecms_core_index',
            [],
            Router::ABSOLUTE_URL
        )));

    }
}
