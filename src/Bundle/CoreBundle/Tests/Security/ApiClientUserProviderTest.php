<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Repository\ApiKeyRepository;
use UniteCMS\CoreBundle\Security\ApiKeyUserProvider;

class ApiClientUserProviderTest extends TestCase
{
    /**
     * Test load user form ApiClientUserProvider
     */
    public function testLoadValidUser() {

        $token = 'ThisIsMyToken';
        $organization = new Organization();
        $organization->setIdentifier('my_organization');

        $request = new Request();
        $request->attributes->set('organization', $organization->getIdentifier());
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $entityRepository = new class extends ApiKeyRepository {
            public $organization;
            public function __construct() {}
            public function findOneByTokenAndOrganization(string $token, string $organization) {
                if($token === 'ThisIsMyToken' && $organization === $this->organization->getIdentifier()) {
                    $client = new ApiKey();
                    $client->setToken($token);
                    $client->setOrganization($this->organization);
                    return $client;
                }

                return null;
            }
        };
        $entityRepository->organization = $organization;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $provider = new ApiKeyUserProvider($requestStack, $entityManager);
        $this->assertTrue($provider->supportsClass(ApiKey::class));

        // Try to load a user from the provider.
        $apiClient = $provider->loadUserByUsername($token);
        $this->assertEquals($token, $apiClient->getToken());
        $this->assertEquals($organization, $apiClient->getOrganization());

        // Try to reload a user from the provider.
        $apiClient = $provider->refreshUser($apiClient);
        $this->assertEquals($token, $apiClient->getToken());
        $this->assertEquals($organization, $apiClient->getOrganization());
    }

    /**
     * Test load invalid user form ApiClientUserProvider
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadInValidUser() {

        $token = 'OtherToken';
        $organization = new Organization();
        $organization->setIdentifier('org');

        $request = new Request();
        $request->attributes->set('organization', $organization->getIdentifier());
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $entityRepository = new class extends ApiKeyRepository {
            public $organization;
            public function __construct() {}
            public function findOneByTokenAndOrganization(string $token, string $organization) {
                if($token === 'ThisIsMyToken' && $organization === $this->organization->getIdentifier()) {
                    $client = new ApiKey();
                    $client->setToken($token);
                    $client->setOrganization($this->organization);
                    return $client;
                }

                return null;
            }
        };
        $entityRepository->organization = $organization;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $provider = new ApiKeyUserProvider($requestStack, $entityManager);
        $this->assertTrue($provider->supportsClass(ApiKey::class));

        // Try to load a user from the provider.
        $provider->loadUserByUsername($token);
    }

    /**
     * Test load invalid user form ApiClientUserProvider
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshInvalidUser() {
        $entityManager = $this->createMock(EntityManager::class);
        $provider = new ApiKeyUserProvider(new RequestStack(), $entityManager);

        // Try to refresh an non ApiClient.
        $provider->refreshUser(new User());
    }
}
