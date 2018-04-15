<?php

namespace UniteCMS\CoreBundle\Tests\SchemaType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeCompilerPass;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class SchemaTypeManagerTest extends ContainerAwareTestCase
{

    public function testSchemaTypeManagerGetterAndSetter()
    {

        // Check that core schemaTypes and factories are already registered via compiler pass.
        $this->assertTrue($this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('Query'));
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('ContentResult')
        );
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('ContentInterface')
        );
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('ContentResultInterface')
        );
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('ContentResult')
        );
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('SettingInterface')
        );
        $this->assertTrue($this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('FilterInput'));
        $this->assertTrue($this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('SortInput'));

        // Test processing container without primary service should return silently.
        $container = new ContainerBuilder();
        $compilerPass = new SchemaTypeCompilerPass();
        $this->assertNull($compilerPass->process($container));

        // Test registering schemaTypes and schemaTypeFactories.
        $schemaType = new class extends ObjectType
        {
            public function __construct()
            {
                parent::__construct(['name' => 'my_anonymous_type']);
            }
        };
        $schemaTypeFactory = new class implements SchemaTypeFactoryInterface
        {
            public function supports(string $schemaTypeName): bool
            {
                return false;
            }

            public function createSchemaType(
                SchemaTypeManager $schemaTypeManager,
                int $nestingLevel,
                Domain $domain = null,
                string $schemaTypeName
            ): Type {
                return new ObjectType([]);
            }
        };

        $this->assertNotContains(
            $schemaType,
            $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypes()
        );
        $this->assertNotContains(
            $schemaTypeFactory,
            $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeFactories()
        );
        $this->assertFalse(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('my_anonymous_type')
        );

        // Now register the schemaType an the schemaTypeFactory as service.
        $this->container->get('unite.cms.graphql.schema_type_manager')->registerSchemaType($schemaType);
        $this->container->get('unite.cms.graphql.schema_type_manager')->registerSchemaTypeFactory($schemaTypeFactory);

        $this->assertContains(
            $schemaType,
            $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypes()
        );
        $this->assertContains(
            $schemaTypeFactory,
            $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaTypeFactories()
        );
        $this->assertTrue(
            $this->container->get('unite.cms.graphql.schema_type_manager')->hasSchemaType('my_anonymous_type')
        );
    }

    /**
     * Register an invalid SchemaType should throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The schema type: 'any_unknown' was not found.
     */
    public function testGetUnknownSchemaType()
    {
        $schemaTypeManager = new SchemaTypeManager();
        $schemaTypeManager->getSchemaType('any_unknown');
    }

    /**
     * Getting an unknown schemaType should throw an exception.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Schema type must be of type GraphQL\Type\Definition\ObjectType or GraphQL\Type\Definition\InputObjectType or GraphQL\Type\Definition\InterfaceType or GraphQL\Type\Definition\UnionType or GraphQL\Type\Definition\ListOfType
     */
    public function testRegisterInvalidSchemaType()
    {
        $schemaTypeManager = new SchemaTypeManager();
        $unsupportedType = new class extends Type
        {
        };
        $schemaTypeManager->registerSchemaType($unsupportedType);
    }

    public function testGettingKnownSchemaType()
    {

        $schemaTypeManager = new SchemaTypeManager();

        // Test registering schemaTypes and schemaTypeFactories.
        $schemaType = new class extends ObjectType
        {
            public function __construct()
            {
                parent::__construct(['name' => 'my_anonymous_type']);
            }
        };
        $schemaTypeFactory = new class implements SchemaTypeFactoryInterface
        {
            public function supports(string $schemaTypeName): bool
            {
                return $schemaTypeName === 'factory_type';
            }

            public function createSchemaType(
                SchemaTypeManager $schemaTypeManager,
                int $nestingLevel,
                Domain $domain = null,
                string $schemaTypeName
            ): Type {
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
