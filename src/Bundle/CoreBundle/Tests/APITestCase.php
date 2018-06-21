<?php

namespace UniteCMS\CoreBundle\Tests;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Controller\GraphQLApiController;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Service\UniteCMSManager;

// Mocked database repository.
class MockedObjectRepository implements ObjectRepository {

    public $objects = [];
    public $className;

    public function __construct($className) {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->objects[$id] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->objects;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->className;
    }
}

// Mocked database repository factory.
class MockedRepositoryFactory implements RepositoryFactory {

    public function getEntityName($object) {
        $classParts = explode('\\', get_class($object));
        $className = array_pop($classParts);
        return 'UniteCMSCoreBundle:'.$className;
    }

    /**
     * @var MockedObjectRepository[] $repositories
     */
    private $repositories = [];

    /**
     * Add an object to the mocked repository.
     * @param $object
     */
    public function add($object) {
        $entityName = $this->getEntityName($object);
        if(!isset($this->repositories[$entityName])) {
            $this->repositories[$entityName] = new MockedObjectRepository($entityName);
        }
        $this->repositories[$entityName]->objects[$object->getId()] = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        if(!isset($this->repositories[$entityName])) {
            $this->repositories[$entityName] = new MockedObjectRepository($entityName);
        }
        return $this->repositories[$entityName];
    }
}

/**
 * An optimized API test case that can be used to test API responses. The API Test Case does not need a database
 * connection, all entity fetching is mocked.
 *
 * Class APITestCase
 * @package UniteCMS\CoreBundle\Tests
 */
abstract class APITestCase extends ContainerAwareTestCase
{

    /**
     * Each entry should hold an domain config json string. The array key will become the identifier. If title is
     * missing, the identifier will be used.
     *
     * @var string[]
     */
    protected $domainConfig = [];

    /**
     * Will hold all parsed domains.
     *
     * @var Domain[] $domains
     */
    protected $domains = [];

    /**
     * Holds the organization for all domains. This will be generated automatically on setUp.
     *
     * @var Organization $organization
     */
    protected $organization = null;

    /**
     * @var GraphQLApiController $controller
     */
    private $controller;

    /**
     * @var
     */
    protected $repositoryFactory;


    public function setUp()
    {
        parent::setUp();

        // Mock database repository factory.
        $this->repositoryFactory = new MockedRepositoryFactory();
        $repoFactory = $this->repositoryFactory;

        // Use our mocked repositoryFactory that just returns the objects from memory.
        $reflector = new \ReflectionProperty(EntityManager::class, 'repositoryFactory');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('doctrine.orm.entity_manager'), $this->repositoryFactory);

        $this->organization = new Organization();
        $this->organization->setTitle('Org')->setIdentifier('org')->setId(1);
        $this->repositoryFactory->add($this->organization);

        // Parse all domains configurations and create domain objects.
        $cnt = 0;
        foreach($this->domainConfig as $identifier => $config) {
            $cnt++;

            $this->domains[$identifier] = static::$container->get('unite.cms.domain_definition_parser')->parse($config);
            $this->domains[$identifier]->setIdentifier($identifier);
            $this->domains[$identifier]->setId($cnt);

            $this->repositoryFactory->add($this->domains[$identifier]);

            if(empty($this->domains[$identifier]->getTitle())) {
                $this->domains[$identifier]->setTitle(ucfirst($identifier));
            }
            $this->organization->addDomain($this->domains[$identifier]);

            $this->domains[$identifier]->getContentTypes()->forAll(function( $key, ContentType $type ) use ($cnt, $repoFactory) {
                $type->setId($cnt);
                $repoFactory->add($type);
            });
            $this->domains[$identifier]->getSettingTypes()->forAll(function( $key, SettingType $type ) use ($cnt, $repoFactory) {
                $type->setId($cnt);
                $repoFactory->add($type);
            });
            $this->domains[$identifier]->getDomainMemberTypes()->forAll(function( $key, DomainMemberType $type ) use ($cnt, $repoFactory) {
                $type->setId($cnt);
                $repoFactory->add($type);
            });

            // Create users & api keys
            $cnt1 = 0;
            foreach($this->domains[$identifier]->getDomainMemberTypes() as $domainMemberType) {
                $cnt1++;

                $user = new User();
                $user
                    ->setName($domainMemberType->getIdentifier())
                    ->setEmail($domainMemberType->getIdentifier().'@test.com')
                    ->setPassword('password');

                $userMember = new DomainMember();
                $userMember->setId($cnt1);
                $userMember->setDomainMemberType($domainMemberType)->setAccessor($user);
                $this->domains[$identifier]->addMember($userMember);

                $apiKey = new ApiKey();
                $apiKey
                    ->setName($domainMemberType->getIdentifier())
                    ->setToken('token');

                $apiKeyMember = new DomainMember();
                $apiKeyMember->setId($cnt1);
                $apiKeyMember->setDomainMemberType($domainMemberType)->setAccessor($apiKey);
                $this->domains[$identifier]->addMember($apiKeyMember);
            }
        }

        $this->controller = new GraphQLApiController();
        $this->controller->setContainer(static::$container);

        // Inject our organization into unite cms manager and set it to initialized.
        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $this->organization);

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'initialized');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), true);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->organization = null;
        $this->domains = [];
    }

    /**
     * Calls the api for the the given domain for the given user. If user is not an API Key, the firewall fallback will
     * be used.
     *
     * @param Domain $domain
     * @param string $query
     * @param array $variables
     * @param string $memberType
     * @param string $userType
     * @return mixed
     */
    protected function api(string $query, Domain $domain = null, array $variables = [], string $memberType  = 'editor', string $userType = ApiKey::class) {

        if(!$domain) {
            $domain = array_values($this->domains)[0];
        }

        /**
         * @var DomainMember $domainMember
         */
        $domainMember = $domain->getMembers()->filter(function( DomainMember $domainMember ) use ($memberType, $userType) {
            return $domainMember->getDomainMemberType()->getIdentifier() === $memberType && get_class($domainMember->getAccessor()) === $userType;
        })->first();

        if(!$domainMember) {
            throw new \InvalidArgumentException("User of type >$userType< was not found as member of type >$memberType< of domain >$domain<.");
        }

        // Fake a real HTTP request.
        $request = new Request([], [], [
            'organization' => $domain->getOrganization(),
            'domain' => $domain,
        ], [], [], [
            'REQUEST_METHOD' => 'POST',
        ], json_encode(['query' => $query, 'variables' => $variables]));


        // Inject domain into unite.cms.manager.
        try {
            $reflector = new \ReflectionProperty(UniteCMSManager::class, 'domain');
            $reflector->setAccessible(true);
            $reflector->setValue(static::$container->get('unite.cms.manager'), $domain);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException("Domain >$domain< is not a valid domain object.");
        }

        // If we fallback to the stateful main firewall, we need to add a csrf-token with the request.
        if(!$domainMember->getAccessor() instanceof ApiKey) {
            $request->headers->set('X-CSRF-TOKEN', static::$container->get('security.csrf.token_manager')->getToken(StringUtil::fqcnToBlockPrefix(FieldableFormType::class))->getValue());
        }

        static::$container->get('security.token_storage')->setToken(new PostAuthenticationGuardToken(
            $domainMember->getAccessor(),
            $domainMember->getAccessor() instanceof ApiKey ? 'api': 'main',
            []
        ));

        $response = $this->controller->indexAction($domain->getOrganization(), $domain, $request);
        return json_decode($response->getContent());
    }

}
