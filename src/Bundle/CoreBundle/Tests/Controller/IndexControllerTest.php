<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.01.18
 * Time: 12:57
 */

namespace UniteCMS\CoreBundle\Tests\Controller;

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class IndexControllerTest extends DatabaseAwareTestCase
{

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $admin
     */
    private $admin;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
        $this->client->disableReboot();

        $this->admin = new User();
        $this->admin->setEmail('editor@example.com')->setName('Domain Admin')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $this->em->persist($this->admin);
        $this->em->flush();
        $this->em->refresh($this->admin);

        $token = new UsernamePasswordToken($this->admin, null, 'main', $this->admin->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testIndexAction() {

        $url = static::$container->get('router')->generate('unitecms_core_index', [], Router::ABSOLUTE_URL);
        $profile_orgs_url = static::$container->get('router')->generate('unitecms_core_organization_index',  [], Router::ABSOLUTE_URL);

        // index redirects to profile organizations route
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect($profile_orgs_url));
        $crawler = $this->client->followRedirect();

        // If there are no organizations for this user, the index action should display an info.
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-warning:contains("' . static::$container->get('translator')->trans('organizations.error.no_organizations') .'")'));

        // Add an organization, but not for this user.
        $org1 = new Organization();
        $org1->setIdentifier('o1_o1')->setTitle('Org 1');
        $this->em->persist($org1);
        $this->em->flush();

        // Should be the same result.
        $this->client->request('GET', $url);
        $crawler = $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-warning:contains("' . static::$container->get('translator')->trans('organizations.error.no_organizations') .'")'));

        // Now invite the user to the organization.
        $org1 = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll()[0];
        $admin = $this->em->getRepository('UniteCMSCoreBundle:User')->findAll()[0];
        $orgMember = new OrganizationMember();
        $orgMember->setUser($admin)->setOrganization($org1);
        $this->em->persist($orgMember);
        $this->em->flush();

        // Because we don't reboot the kernel on each request, clear all previous created but not persisted entities.
        $this->em->clear();

        // Index should now redirect to first organization.
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($org1->getIdentifier()),
        ], Router::ABSOLUTE_URL)));

        // Create a 2nd organization.
        $org2 = new Organization();
        $org2->setIdentifier('o2')->setTitle('Org 2');
        $this->em->persist($org2);
        $this->em->flush();

        // Should be the same result.
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($org1->getIdentifier()),
        ], Router::ABSOLUTE_URL)));

        // Now invite the user to the 2nd organization.
        $org2 = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll()[1];
        $admin = $this->em->getRepository('UniteCMSCoreBundle:User')->findAll()[0];
        $org2Member = new OrganizationMember();
        $org2Member->setUser($admin)->setOrganization($org2);
        $this->em->persist($org2Member);
        $this->em->flush();

        // Because we don't reboot the kernel on each request, clear all previous created but not persisted entities.
        $this->em->clear();

        // Index should now show a menu with all organizations for this user.
        $this->client->request('GET', $url);
        $crawler = $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('a:contains("Org 1")'));
        $this->assertCount(1, $crawler->filter('a:contains("Org 2")'));

        // Create a 3rd organization.
        $org3 = new Organization();
        $org3->setIdentifier('o3')->setTitle('Org 3');
        $this->em->persist($org3);
        $this->em->flush();

        // Should be the same result.
        $this->client->request('GET', $url);
        $crawler = $this->client->followRedirect();
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('a:contains("Org 1")'));
        $this->assertCount(1, $crawler->filter('a:contains("Org 2")'));
    }
}
