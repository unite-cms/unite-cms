<?php


namespace UniteCMS\CoreBundle\Tests\Domain;

use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class DomainParameterReplacementTest extends SchemaAwareTestCase
{
    public function testParameterReplacement() {

        $this->buildSchema('
            """Test: %(ENV_TEST)%"""
            type Test implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                field: String @textField(default: "%(ENV_TEST2)%")
            }
        ');

        $domain = static::$container->get(DomainManager::class)->current();
        $type = $domain->getContentTypeManager()->getContentType('Test');
        $this->assertEquals('Test: FOO', $type->getName());
        $field = $type->getField('field');
        $this->assertEquals([
            [
                'name' => 'textField',
                'args' => [
                    'default' => 'BAA',
                ],
            ]
        ], $field->getDirectives());
    }
}
