<?php

namespace UniteCMS\CoreBundle\Tests\Domain;

use GraphQL\Utils\SchemaPrinter;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\BypassAccessCheckExecutionContext;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class DomainSchemaDefinitionTest extends SchemaAwareTestCase
{
    public function testCombinedSchemaDefinion() {
        static::$container->get(DomainManager::class)
            ->clearDomain()
            ->setCurrentDomainFromConfigId('test')
            ->current();

        // Try to mutate without permissions
        $result = static::$container->get(SchemaManager::class)->executeOperation('createMyTest', ['myTestFields'], ['f1' => 'Foo', 'f2' => 'Baa']);
        $this->assertNull($result->data);
        $this->assertCount(1, $result->errors);
        $this->assertEquals('Cannot query field "createMyTestType" on type "Mutation".', $result->errors[0]->getMessage());

        // Try to mutate with bypass permissions
        $result = static::$container->get(SchemaManager::class)->executeOperation('createMyTest', ['myTestFields'], ['f1' => 'Foo', 'f2' => 'Baa'], new BypassAccessCheckExecutionContext(), true);
        $this->assertEmpty($result->errors);
        $this->assertEquals(['createMyTestType' => [
            'field1' => 'Foo',
            'field2' => 'Baa',
        ]], $result->data);
    }
}
