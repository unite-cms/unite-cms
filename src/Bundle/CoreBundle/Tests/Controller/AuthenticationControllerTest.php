<?php

namespace UniteCMS\CoreBundle\Tests\Controller;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

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
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
        $this->client->disableReboot();
    }

    public function testLoginWithValidUser() {

        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setName('Example')
            ->setPassword(static::$container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL));
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = $password;
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_index', [], Router::ABSOLUTE_URL)));
    }

    public function testLoginWithInValidUser() {
        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setName('Example')
            ->setPassword(static::$container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL));
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail() . 'invalid';
        $form['_password'] = $password;
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("Invalid credentials.")'));
    }

    public function testLoginWithInValidPassword() {
        $password = 'password';

        $user = new User();
        $user
            ->setEmail('user@example.com')
            ->setName('Example')
            ->setPassword(static::$container->get('security.password_encoder')->encodePassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL));
        $form = $crawler->filter('form')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = $password . 'invalid';
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("Invalid credentials.")'));
    }

}
