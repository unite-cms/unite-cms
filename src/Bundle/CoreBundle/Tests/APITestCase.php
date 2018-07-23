<?php

namespace UniteCMS\CoreBundle\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Controller\GraphQLApiController;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\Types\ReferenceFieldType;
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
        // We mock all findBy calls here, that we need.
        $result = [];

        if(array_keys($criteria) == ['identifier']) {
            foreach ($this->objects as $key => $object) {

                if(!is_array($criteria['identifier'])) {
                    $criteria['identifier'] = [$criteria['identifier']];
                }

                if(in_array($object->getIdentifier(), $criteria['identifier'])) {
                    $result[$key] = $object;
                }
            }
        } else {
            dump("findBy");
            dump($this->getClassName());
            dump(array_keys($criteria));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        // We mock all findOneBy calls here, that we need.
        if(array_keys($criteria) == ['domain', 'identifier']) {
            foreach($this->objects as $object) {
                if($object->getDomain()->getId() == $criteria['domain']->getId() && $object->getIdentifier() == $criteria['identifier']) {
                    return $object;
                }
            }
        }

        if(array_key_exists('id', $criteria)) {
            return $this->find($criteria['id']);
        }

        return null;
    }

    public function createQueryBuilder($alias, $indexBy = null) {

        $objects = $this->objects;

        $mockedQueryBuilder = new class extends QueryBuilder {
            public $objects;
            public $contentTypeIdentifiers = [];

            public function __construct() {}
            public function setParameter($key, $value, $type = null) {

                if($key === ':contentTypes') {

                    if(!is_array($value)) {
                        $value = [$value];
                    }

                    foreach($value as $type) {
                        if($type instanceof ContentType) {
                            $this->contentTypeIdentifiers[] = $type->getIdentifier();
                        } else {
                            $this->contentTypeIdentifiers[] = $type;
                        }
                    }
                }

                return $this;
            }
            public function getQuery()
            {
                $objects = [];

                if(empty($this->contentTypeIdentifiers)) {
                   $objects = $this->objects;
                } else {
                    foreach($this->objects as $object) {
                        if(in_array($object->getContentType()->getIdentifier(), $this->contentTypeIdentifiers)) {
                            $objects[] = $object;
                        }
                    }
                }

                // For the moment, just return all objects here.
                return new ArrayCollection($objects);
            }
        };
        $mockedQueryBuilder->objects = $objects;

        return $mockedQueryBuilder;
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

        if(empty($object->getId())) {
            try {
                $reflector = new \ReflectionProperty(get_class($object), 'id');
                $reflector->setAccessible(true);
                $reflector->setValue($object, count($this->repositories[$entityName]->objects) + 1);
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException('We can only handle objects with an id property');
            }

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
     * @var MockedRepositoryFactory $repositoryFactory
     */
    protected $repositoryFactory;


    public function setUp()
    {
        parent::setUp();

        // Mock database repository factory.
        $this->repositoryFactory = new MockedRepositoryFactory();

        // Use our mocked repositoryFactory that just returns the objects from memory.
        $em = static::$container->get('doctrine.orm.entity_manager');
        $reflector = new \ReflectionProperty(EntityManager::class, 'repositoryFactory');
        $reflector->setAccessible(true);
        $reflector->setValue($em, $this->repositoryFactory);

        // We also need to set this to the reference field type. Not 100% sure, why the field type is not getting our overridden doctrine.orm.entity_manager instance.
        $reflector = new \ReflectionProperty(ReferenceFieldType::class, 'entityManager');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference'), $em);



        $this->organization = new Organization();
        $this->organization->setTitle('Org')->setIdentifier('org');
        $this->repositoryFactory->add($this->organization);

        // Parse all domains configurations and create domain objects.
        foreach($this->domainConfig as $identifier => $config) {
            $this->domains[$identifier] = static::$container->get('unite.cms.domain_definition_parser')->parse($config);
            $this->domains[$identifier]->setIdentifier($identifier);

            $this->repositoryFactory->add($this->domains[$identifier]);

            if(empty($this->domains[$identifier]->getTitle())) {
                $this->domains[$identifier]->setTitle(ucfirst($identifier));
            }
            $this->organization->addDomain($this->domains[$identifier]);

            foreach($this->domains[$identifier]->getContentTypes() as $type) { $this->repositoryFactory->add($type); }
            foreach($this->domains[$identifier]->getSettingTypes() as $type) { $this->repositoryFactory->add($type); }
            foreach($this->domains[$identifier]->getDomainMemberTypes() as $type) { $this->repositoryFactory->add($type); }

            // Create users & api keys
            foreach($this->domains[$identifier]->getDomainMemberTypes() as $domainMemberType) {

                $orgMember = new OrganizationMember();
                $orgMember->setOrganization($this->organization);

                $user = new User();
                $user
                    ->addOrganization($orgMember)
                    ->setName($domainMemberType->getIdentifier())
                    ->setEmail($domainMemberType->getIdentifier().'@test.com')
                    ->setPassword('password');

                $userMember = new DomainMember();
                $userMember->setDomainMemberType($domainMemberType)->setAccessor($user)->setDomain($this->domains[$identifier]);
                $user->addDomain($userMember);
                $this->domains[$identifier]->addMember($userMember);

                $apiKey = new ApiKey();
                $apiKey
                    ->setOrganization($this->organization)
                    ->setName($domainMemberType->getIdentifier())
                    ->setToken('token');

                $apiKeyMember = new DomainMember();
                $apiKeyMember->setDomainMemberType($domainMemberType)->setAccessor($apiKey)->setDomain($this->domains[$identifier]);
                $apiKey->addDomain($apiKeyMember);
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
