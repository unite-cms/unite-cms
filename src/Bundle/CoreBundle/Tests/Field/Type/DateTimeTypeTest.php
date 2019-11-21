<?php


namespace UniteCMS\CoreBundle\Tests\Field\Type;

use DateTime;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class DateTimeTypeTest extends SchemaAwareTestCase
{
    public function testQueryMutate() {

        $this->buildSchema('
            type Test implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                field: DateTime @dateTimeField
            }
        ');

        $create = 'mutation($data: TestInput!) { createTest(data: $data, persist: true) { id, field }}';

        // Create integer value
        $dateTime = (new DateTime())->format('c');

        $this->assertGraphQL([
            'createTest' => [
                'id' => '{id}',
                'field' => $dateTime
            ],
        ], $create, ['data' => ['field' => $dateTime]]);
    }
}
