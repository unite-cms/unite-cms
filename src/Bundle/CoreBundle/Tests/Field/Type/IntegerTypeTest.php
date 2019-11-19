<?php


namespace UniteCMS\CoreBundle\Tests\Field\Type;


use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class IntegerTypeTest extends SchemaAwareTestCase
{
    public function testQueryMutate() {

        $this->buildSchema('
            type Test implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                field: Int @integerField
            }
        ');

        $create = 'mutation($data: TestInput!) { createTest(data: $data, persist: true) { id, field }}';

        // Create integer value
        $this->assertGraphQL([
            'createTest' => [
                'id' => '{id}',
                'field' => 42
            ],
        ], $create, ['persist' => false, 'data' => ['field' => 42]]);
    }
}
