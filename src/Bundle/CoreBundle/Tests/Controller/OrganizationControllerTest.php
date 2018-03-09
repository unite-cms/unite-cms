<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.01.18
 * Time: 12:57
 */

namespace UnitedCMS\CoreBundle\Tests\Controller;


use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class OrganizationControllerTest extends DatabaseAwareTestCase
{

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $admin
     */
    private $admin;

    /**
     * @var Organization $organization
     */
    private $organization;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        $this->admin = new User();
        $this->admin->setEmail('editor@example.com')->setFirstname('Domain Admin')->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword('XXX');
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

        $url = $this->container->get('router')->generate('unitedcms_core_organizations');

        // If there are no organizations for this user, the index action should display an info.
        $crawler = $this->client->request('GET', $url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-warning p:contains("You are not invited to any organization. Please contact the system administrator.")'));

        // Add an organization, but not for this user.
        $org1 = new Organization();
        $org1->setIdentifier('o1')->setTitle('Org 1');
        $this->em->persist($org1);
        $this->em->flush();

        // Should be the same result.
        $crawler = $this->client->request('GET', $url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-warning p:contains("You are not invited to any organization. Please contact the system administrator.")'));

        // Now invite the user to the organization.
        $org1 = $this->em->getRepository('UnitedCMSCoreBundle:Organization')->findAll()[0];
        $admin = $this->em->getRepository('UnitedCMSCoreBundle:User')->findAll()[0];
        $orgMember = new OrganizationMember();
        $orgMember->setUser($admin)->setOrganization($org1);
        $this->em->persist($orgMember);
        $this->em->flush();

        // Index should now redirect to first organization.
        $crawler = $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_domain_index', [
            'organization' => $org1->getIdentifier(),
        ])));

        // Create a 2nd organization.
        $org2 = new Organization();
        $org2->setIdentifier('o2')->setTitle('Org 2');
        $this->em->persist($org2);
        $this->em->flush();

        // Should be the same result.
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_domain_index', [
            'organization' => $org1->getIdentifier(),
        ])));

        // Now invite the user to the 2nd organization.
        $org2 = $this->em->getRepository('UnitedCMSCoreBundle:Organization')->findAll()[1];
        $admin = $this->em->getRepository('UnitedCMSCoreBundle:User')->findAll()[0];
        $org2Member = new OrganizationMember();
        $org2Member->setUser($admin)->setOrganization($org2);
        $this->em->persist($org2Member);
        $this->em->flush();

        // Index should now show a menu with all organizations for this user.
        $crawler = $this->client->request('GET', $url);
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
        $crawler = $this->client->request('GET', $url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('a:contains("Org 1")'));
        $this->assertCount(1, $crawler->filter('a:contains("Org 2")'));
    }
}