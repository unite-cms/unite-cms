<?php

namespace UniteCMS\CoreBundle\Tests\SchemaType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Contracts\Cache\CacheInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeAlterationInterface;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeCompilerPass;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;
use UniteCMS\CoreBundle\Tests\Mocks\SchemaTypeAlterationMock;

class SchemaTypeManagerTest extends ContainerAwareTestCase {

    public function testSchemaTypeManagerGetterAndSetter() {

        // Check that core schemaTypes and factories are already registered via compiler pass.
        $this->assertTrue(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('Query'));
        $this->assertTrue(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('FieldableContentInterface'));
        $this->assertTrue(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('FilterInput'));
        $this->assertTrue(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('SortInput'));

        // Test processing container without primary service should return silently.
        $container = new ContainerBuilder();
        $compilerPass = new SchemaTypeCompilerPass();
        $this->assertNull($compilerPass->process($container));

        // Test registering schemaTypes and schemaTypeFactories.
        $schemaType = new class extends ObjectType {
            public function __construct() { parent::__construct(['name' => 'my_anonymous_type']); }
        };
        $schemaTypeFactory = new class implements SchemaTypeFactoryInterface {
            public function supports(string $schemaTypeName): bool { return false; }
            public function createSchemaType(SchemaTypeManager $schemaTypeManager, Domain $domain = null, string $schemaTypeName): Type
            {
                return new ObjectType([]);
            }
        };

        $schemaTypeAlteration = new class implements SchemaTypeAlterationInterface {
            public function supports(string $schemaTypeName): bool { return false; }
            public function alter(Type $schemaType): void {}
        };

        $this->assertNotContains($schemaType, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypes());
        $this->assertNotContains($schemaTypeFactory, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeFactories());
        $this->assertNotContains($schemaTypeAlteration, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeAlterations());
        $this->assertFalse(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('my_anonymous_type'));

        // Now register the schemaType an the schemaTypeFactory as service.
        static::$container->get('unite.cms.graphql.schema_type_manager')->registerSchemaType($schemaType);
        static::$container->get('unite.cms.graphql.schema_type_manager')->registerSchemaTypeFactory($schemaTypeFactory);
        static::$container->get('unite.cms.graphql.schema_type_manager')->registerSchemaTypeAlteration($schemaTypeAlteration);

        $this->assertContains($schemaType, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypes());
        $this->assertContains($schemaTypeFactory, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeFactories());
        $this->assertContains($schemaTypeAlteration, static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeAlterations());
        $this->assertTrue(static::$container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('my_anonymous_type'));
    }

    /**
     * Register an invalid SchemaType should throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The schema type: 'any_unknown' was not found.
     */
    public function testGetUnknownSchemaType() {
        $schemaTypeManager = new SchemaTypeManager($this->createMock(CacheInterface::class), $this->createMock(Security::class));
        $schemaTypeManager->getSchemaType('any_unknown');
    }

    public function testAlterType() {

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        static::$container->get('security.token_storage')->setToken(
            new PostAuthenticationGuardToken($admin, 'api', [])
        );

        $schemaTypeManager = new SchemaTypeManager($this->createMock(CacheInterface::class), $this->createMock(Security::class));

        $schemaTypeManager->registerSchemaTypeAlteration(new SchemaTypeAlterationMock('Test1'));

        $schemaTypeManager->registerSchemaType(new ObjectType([
            'name' => 'Test1',
            'fields' => function(){
                return [ 'foo' => Type::string(), ];
            },
            'resolveField' => function($value, array $args, $context, ResolveInfo $info){
                return 'YAY';
            },
        ]));
        $schemaTypeManager->registerSchemaType(new ObjectType([
            'name' => 'Test2',
            'fields' => function(){
                return [ 'foo' => Type::string(), ];
            },
            'resolveField' => function($value, array $args, $context, ResolveInfo $info){
                return 'YAY';
            },
        ]));

        $this->assertEquals([
            'foo' => Type::string(),
            'altered' => Type::string(),
        ], $schemaTypeManager->getSchemaType('Test1')->config['fields']());

        $this->assertEquals([
            'foo' => Type::string(),
        ], $schemaTypeManager->getSchemaType('Test2')->config['fields']());


        // Functional test for Query schema manipulation.
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), new Domain());

        // In config/packages/test/services.yaml we defined the alteration.
        $this->assertArrayHasKey('altered', static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType('Query')->getFields());
        $this->assertArrayNotHasKey('altered', static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType('Mutation')->getFields());
    }

    /**
     * Getting an unknown schemaType should throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Schema type must be of type GraphQL\Type\Definition\ObjectType or GraphQL\Type\Definition\InputObjectType or GraphQL\Type\Definition\InterfaceType or GraphQL\Type\Definition\UnionType or GraphQL\Type\Definition\ListOfType
     */
    public function testRegisterInvalidSchemaType() {
        $schemaTypeManager = new SchemaTypeManager($this->createMock(CacheInterface::class), $this->createMock(Security::class));
        $unsupportedType = new class extends Type {};
        $schemaTypeManager->registerSchemaType($unsupportedType);
    }

    public function testGettingKnownSchemaType() {

        $schemaTypeManager = new SchemaTypeManager($this->createMock(CacheInterface::class), $this->createMock(Security::class));

        // Test registering schemaTypes and schemaTypeFactories.
        $schemaType = new class extends ObjectType {
            public function __construct() { parent::__construct(['name' => 'my_anonymous_type']); }
        };
        $schemaTypeFactory = new class implements SchemaTypeFactoryInterface {
            public function supports(string $schemaTypeName): bool { return $schemaTypeName === 'factory_type'; }
            public function createSchemaType(SchemaTypeManager $schemaTypeManager, Domain $domain = null, string $schemaTypeName): Type
            {
                return new ObjectType(['name' => 'factory_type']);
            }
        };
        $schemaTypeManager->registerSchemaType($schemaType);
        $schemaTypeManager->registerSchemaTypeFactory($schemaTypeFactory);

        // Accessing the registered schemaType should return it.
        $this->assertEquals($schemaType, $schemaTypeManager->getSchemaType('my_anonymous_type'));

        // Accessing any unknown type should ask all factories if they can provide this type.
        $factoryType = $schemaTypeManager->getSchemaType('factory_type');
        $this->assertNotNull($factoryType);
        $this->assertEquals('factory_type', $factoryType->name);

        // Accessing any unknown type, that is not supported by any factory, should throws an exception.
        $m = "";
        try {
            $schemaTypeManager->getSchemaType('any_unknown');
        } catch (\InvalidArgumentException $e) {
            $m = $e->getMessage();
        }
        $this->assertEquals("The schema type: 'any_unknown' was not found.", $m);
    }
}
