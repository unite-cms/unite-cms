<?php

namespace UnitedCMS\CoreBundle\Tests\Controller;

use Symfony\Component\HttpKernel\Client;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class AuthenticationControllerTest extends DatabaseAwareTestCase {

    /**
     * @var Client $client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);
    }

    public function testLoginWithValidUser() {

        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setFirstname('Example')
            ->setLastname('Example');
        $user->setPassword($this->container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = $password;
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/'));
    }

    public function testLoginWithInValidUser() {
        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setFirstname('Example')
            ->setLastname('Example');
        $user->setPassword($this->container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail() . 'invalid';
        $form['_password'] = $password;
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));
        $crawler = $this->client->followRedirect();
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("Invalid credentials.")'));
    }

    public function testLoginWithInValidPassword() {
        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setFirstname('Example')
            ->setLastname('Example');
        $user->setPassword($this->container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = $password . 'invalid';
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/login'));
        $crawler = $this->client->followRedirect();
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("Invalid credentials.")'));
    }

}