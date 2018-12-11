<?php

namespace UniteCMS\CoreBundle\Tests\Controller;

use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class PaginationOrgAndDomainListControllerTest extends DatabaseAwareTestCase {

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var Organization $organization
     */
    private $organization;

    public function setUp()
    {
        parent::setUp();

        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org')->setId(1);
        $this->em->persist($this->organization);

        $user = new User();
        $user->setRoles([User::ROLE_PLATFORM_ADMIN])->setName('User')->setEmail('user@example.com')->setPassword('password');
        $this->em->persist($user);

        $this->em->flush();

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        static::$container->get('security.token_storage')->setToken($token);

        $this->client = static::$container->get('test.client');
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testOrgUserAction() {

        $route = static::$container->get('router')->generate('unitecms_core_organizationuser_index', [
            'organization' => 'org',
        ]);

        // Assert empty user table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(1, $crawler->filter('table'));
        $this->assertCount(0, $crawler->filter('tbody tr'));


        // Create 20 users and 20 invites
        for($i = 1; $i <= 21; $i++) {
            $user = new User();
            $user->setEmail('u'.$i.'@example.com')->setName('U' . $i)->setPassword('password');
            $orgMember = new OrganizationMember();
            $orgMember->setUser($user);
            $this->organization->addMember($orgMember);
            $this->em->persist($user);
            $this->em->persist($orgMember);

            $invite = new Invitation();
            $invite->setEmail('i'.$i.'@example.com')->setOrganization($this->organization)->setToken('X'.$i);
            $this->em->persist($invite);
        }
        $this->em->flush();

        // Assert paginated user table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u1@example.com")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u10@example.com")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("u11@example.com")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i1@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i10@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));


        // Go to next user page
        $nextPaginationLink = $crawler->filter('article.full-content-card')->first()->filter('.navigation li a')->first();
        $crawler = $this->client->click($nextPaginationLink->link());
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u11@example.com")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u20@example.com")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("u9@example.com")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("u21@example.com")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i1@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i10@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));

        // Go to next invitation page
        $crawler = $this->client->request('GET', $route);
        $nextPaginationLink = $crawler->filter('article.full-content-card')->last()->filter('.navigation li a')->first();
        $crawler = $this->client->click($nextPaginationLink->link());
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u1@example.com")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("u10@example.com")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("u11@example.com")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i20@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i21@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i9@example.com")'));
    }

    public function testOrgApiKeyAction() {

        $route = static::$container->get('router')->generate('unitecms_core_organizationapikey_index', [
            'organization' => 'org',
        ]);

        // Assert empty user table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(1, $crawler->filter('table'));
        $this->assertCount(0, $crawler->filter('tbody tr'));


        // Create 20 api keys and 20 invites
        for($i = 1; $i <= 21; $i++) {
            $apiKey = new ApiKey();
            $apiKey->setName('key'.$i.'x');
            $this->organization->addApiKey($apiKey);
            $this->em->persist($apiKey);
        }
        $this->em->flush();

        // Assert paginated api key table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(1, $crawler->filter('table'));

        $apiKeyTable = $crawler->filter('table')->first();
        $this->assertCount(10, $apiKeyTable->filter('tbody tr'));
        $this->assertCount(1, $apiKeyTable->filter('tbody tr td h2:contains("key1x")'));
        $this->assertCount(1, $apiKeyTable->filter('tbody tr td h2:contains("key10x")'));
        $this->assertCount(0, $apiKeyTable->filter('tbody tr td h2:contains("key11x")'));

        // Go to next user page
        $nextPaginationLink = $crawler->filter('article.full-content-card')->first()->filter('.navigation li a')->first();
        $crawler = $this->client->click($nextPaginationLink->link());
        $this->assertCount(1, $crawler->filter('table'));

        $apiKeyTable = $crawler->filter('table')->first();
        $this->assertCount(10, $apiKeyTable->filter('tbody tr'));
        $this->assertCount(1, $apiKeyTable->filter('tbody tr td h2:contains("key11x")'));
        $this->assertCount(1, $apiKeyTable->filter('tbody tr td h2:contains("key20x")'));
        $this->assertCount(0, $apiKeyTable->filter('tbody tr td h2:contains("key9x")'));
        $this->assertCount(0, $apiKeyTable->filter('tbody tr td h2:contains("key21x")'));
    }

    public function testDomainUserAction() {

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($this->organization);
        $this->em->persist($domain);
        $this->em->flush();

        $route = static::$container->get('router')->generate('unitecms_core_domainmember_index', [
            'organization' => 'org',
            'domain' => $domain->getIdentifier(),
            'member_type' => $domain->getDomainMemberTypes()->first()->getIdentifier(),
        ]);

        // Assert empty user table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(1, $crawler->filter('table'));
        $this->assertCount(0, $crawler->filter('tbody tr'));


        // Create 20 users and 20 invites
        for($i = 1; $i <= 21; $i++) {
            $user = new User();
            $user->setEmail('u'.$i.'@example.com')->setName('U' . $i.'x')->setPassword('password');
            $domainMember = new DomainMember();
            $domainMember->setAccessor($user)->setDomainMemberType($domain->getDomainMemberTypes()->first());
            $domain->addMember($domainMember);
            $this->em->persist($user);
            $this->em->persist($domainMember);

            $invite = new Invitation();
            $invite->setEmail('i'.$i.'@example.com')->setOrganization($this->organization)->setToken('X'.$i)
                ->setDomainMemberType($domain->getDomainMemberTypes()->first());
            $this->em->persist($invite);
        }
        $this->em->flush();

        // Assert paginated user table
        $crawler = $this->client->request('GET', $route);
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U1x")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U10x")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("U11x")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i1@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i10@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));


        // Go to next user page
        $nextPaginationLink = $crawler->filter('article.full-content-card')->first()->filter('.navigation li a')->first();
        $crawler = $this->client->click($nextPaginationLink->link());
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U11x")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U20x")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("U9x")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("U21x")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i1@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i10@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));

        // Go to next invitation page
        $crawler = $this->client->request('GET', $route);
        $nextPaginationLink = $crawler->filter('article.full-content-card')->last()->filter('.navigation li a')->first();
        $crawler = $this->client->click($nextPaginationLink->link());
        $this->assertCount(2, $crawler->filter('table'));

        $userTable = $crawler->filter('table')->first();
        $this->assertCount(10, $userTable->filter('tbody tr'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U1x")'));
        $this->assertCount(1, $userTable->filter('tbody tr td:contains("U10x")'));
        $this->assertCount(0, $userTable->filter('tbody tr td:contains("U11x")'));

        $inviteTable = $crawler->filter('table')->last();
        $this->assertCount(10, $inviteTable->filter('tbody tr'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i11@example.com")'));
        $this->assertCount(1, $inviteTable->filter('tbody tr td:contains("i20@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i21@example.com")'));
        $this->assertCount(0, $inviteTable->filter('tbody tr td:contains("i9@example.com")'));
    }
}
