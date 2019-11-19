<?php


namespace UniteCMS\CoreBundle\Tests\Field\Type;


use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class FloatTypeTest extends SchemaAwareTestCase
{
    public function testQueryMutate() {

        $this->buildSchema('
            type Test implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                field: Float @floatField
            }
        ');

        $create = 'mutation($data: TestInput!) { createTest(data: $data, persist: true) { id, field }}';

        // Create integer value
        $this->assertGraphQL([
            'createTest' => [
                'id' => '{id}',
                'field' => 42.5
            ],
        ], $create, ['persist' => false, 'data' => ['field' => 42.5]]);
    }
}
