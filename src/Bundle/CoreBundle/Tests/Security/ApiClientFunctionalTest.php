<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ApiClientFunctionalTest extends DatabaseAwareTestCase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var Domain
     */
    private $domain2;

    /**
     * @var ApiKey $apiClient1
     */
    private $apiClient1;

    /**
     * @var ApiKey $apiClient2
     */
    private $apiClient2;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller access check domain",
    "identifier": "access_check", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1"
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1"
      }
    ]
  }';

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test controller access check')->setIdentifier('access_check');
        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->domain2 = new Domain();
        $this->domain2->setIdentifier('domain2')->setTitle('Domain2')->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->persist($this->domain2);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);
        $this->em->refresh($this->domain2);

        // Create Test API Client
        $this->apiClient1 = new ApiKey();
        $this->apiClient1->setOrganization($this->organization);
        $domainMember = new DomainMember();
        $domainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('viewer'));
        $this->apiClient1
            ->setName('API Client 1')
            ->setToken('xxx')
            ->addDomain($domainMember);

        $this->apiClient2 = new ApiKey();
        $this->apiClient2->setOrganization($this->organization);
        $domainMember = new DomainMember();
        $domainMember->setDomain($this->domain2)->setDomainMemberType($this->domain2->getDomainMemberTypes()->get('viewer'));
        $this->apiClient2
            ->setName('API Client 2')
            ->setToken('yyy')
            ->addDomain($domainMember);

        $this->em->persist($this->apiClient1);
        $this->em->persist($this->apiClient2);

        $this->em->flush();
        $this->em->refresh($this->apiClient1);
        $this->em->refresh($this->apiClient2);
    }

    public function testAccessAPIEndpoint() {

        // Try to access without token.
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
        ], Router::ABSOLUTE_URL), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['query' => '{}']));

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());

        // Try to access with wrong token.
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
            'token' => $this->apiClient2->getToken(),
        ], Router::ABSOLUTE_URL), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['query' => '{}']));

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Try to access with valid token.
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
            'token' => $this->apiClient1->getToken(),
        ], Router::ABSOLUTE_URL), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['query' => '{}']));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Try to access with fallback but no user loggedin.
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
        ], Router::ABSOLUTE_URL), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
        ], json_encode(['query' => '{}']));

        // Redirect to login page
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        // Try to access with fallback but no user access.
        $user1 = new User();
        $user1->setName('User 1')->setEmail('u1@example.com')->setPassword('X');
        $this->em->persist($user1);
        $this->em->flush();
        $this->em->refresh($user1);
        $token = new UsernamePasswordToken($user1, null, 'main', $user1->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
        ], Router::ABSOLUTE_URL), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
        ], json_encode(['query' => '{}']));

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Try to access with fallback and user access.
        $user2 = new User();
        $user2->setName('User 2')->setEmail('u1@example.com')->setPassword('X');
        $user2->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->refresh($user2);
        $token = new UsernamePasswordToken($user2, null, 'main', $user2->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
        $this->client->request('POST', $this->container->get('router')->generate('unitecms_core_api', [
            'domain' => $this->domain->getIdentifier(),
            'organization' => $this->organization->getIdentifier(),
        ], Router::ABSOLUTE_URL), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
        ], json_encode(['query' => '{}']));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
}
