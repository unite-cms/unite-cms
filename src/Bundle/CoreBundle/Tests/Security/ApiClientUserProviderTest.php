<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\ApiClientUserProvider;
use UniteCMS\CoreBundle\Service\UniteCMSManager;

class ApiClientUserProviderTest extends TestCase
{
    /**
     * Test load user form ApiClientUserProvider
     */
    public function testLoadValidUser() {

        $token = 'ThisIsMyToken';
        $domain = new Domain();
        $domain->setIdentifier('my_domain');
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())
            ->method('getDomain')
            ->willReturn($domain);

        $entityRepository = new class extends EntityRepository {
            public function __construct() {}
            public function findOneBy(array $criteria, array $orderBy = NULL) {
                if($criteria['token'] === 'ThisIsMyToken') {
                    $client = new ApiClient();
                    $client->setDomain($criteria['domain']);
                    $client->setToken($criteria['token']);
                    return $client;
                }

                return null;
            }
        };

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $provider = new ApiClientUserProvider($cmsManager, $entityManager);
        $this->assertTrue($provider->supportsClass(ApiClient::class));

        // Try to load a user from the provider.
        $apiClient = $provider->loadUserByUsername($token);
        $this->assertEquals($token, $apiClient->getToken());
        $this->assertEquals($domain, $apiClient->getDomain());

        // Try to reload a user from the provider.
        $apiClient = $provider->refreshUser($apiClient);
        $this->assertEquals($token, $apiClient->getToken());
        $this->assertEquals($domain, $apiClient->getDomain());
    }

    /**
     * Test load invalid user form ApiClientUserProvider
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\TokenNotFoundException
     */
    public function testLoadInValidUser() {

        $token = 'OtherToken';
        $domain = new Domain();
        $domain->setIdentifier('my_domain');
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())
            ->method('getDomain')
            ->willReturn($domain);

        $entityRepository = new class extends EntityRepository {
            public function __construct() {}
            public function findOneBy(array $criteria, array $orderBy = NULL) {
                if($criteria['token'] === 'ThisIsMyToken') {
                    $client = new ApiClient();
                    $client->setDomain($criteria['domain']);
                    $client->setToken($criteria['token']);
                    return $client;
                }

                return null;
            }
        };

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $provider = new ApiClientUserProvider($cmsManager, $entityManager);
        $this->assertTrue($provider->supportsClass(ApiClient::class));

        // Try to load a user from the provider.
        $provider->loadUserByUsername($token);
    }

    /**
     * Test load invalid user form ApiClientUserProvider
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshInvalidUser() {
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $entityManager = $this->createMock(EntityManager::class);
        $provider = new ApiClientUserProvider($cmsManager, $entityManager);

        // Try to refresh an non ApiClient.
        $provider->refreshUser(new User());
    }
}
