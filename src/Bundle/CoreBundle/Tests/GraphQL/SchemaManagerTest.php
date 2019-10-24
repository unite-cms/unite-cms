<?php


namespace UniteCMS\CoreBundle\Tests\GraphQL;

use GraphQL\Utils\BuildSchema;
use GraphQL\Language\AST\DocumentNode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class SchemaManagerTest extends KernelTestCase
{
    /**
     * @expectedException \GraphQL\Error\InvariantViolation
     */
    public function testBasicInValidSchemaCreation()
    {
        $this->assertValidSchema('
            type Article implements UniteContent {
                foo: String
            }
        ');
        $this->addToAssertionCount(1);
    }

    public function testBasicValidSchemaCreation()
    {
        $this->assertValidSchema('
            type Article implements UniteContent {
                id: ID
                _meta: UniteContentMeta!
                title: String @textField(type: "text")
            }
        ');
        $this->addToAssertionCount(1);
    }

    public function testBasicContentCrud() {

        $this->buildSchema('
            type Article implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta!
                title: String @textField(type: "text")
            }
        ');

        $schemaManager = static::$container->get(SchemaManager::class);

        $create = 'mutation($data: ArticleInput!, $persist: Boolean!) { createArticle(data: $data, persist: $persist) { id, title }}';
        $update = 'mutation($id: ID!, $data: ArticleInput!, $persist: Boolean!) { updateArticle(id: $id, data: $data, persist: $persist) { id, title }}';
        $delete = 'mutation($id: ID!, $persist: Boolean!) { deleteArticle(id: $id, persist: $persist) { id, title }}';
        $recover = 'mutation($id: ID!, $persist: Boolean!) { recoverArticle(id: $id, persist: $persist) { id, title }}';
        $get = 'query($id: ID!) { getArticle(id: $id) { id, title }}';
        $find = 'query { findArticle { total, result { id, title }}}';

        // Create article without persist
        $result = $schemaManager->execute($create, ['persist' => false, 'data' => ['title' => 'Foo']])->toArray(true);
        $this->assertEquals(['data' => [
            'createArticle' => [
                'id' => null,
                'title' => 'Foo'
            ],
        ]], $result);


        // Create article with persist
        $result = $schemaManager->execute($create, ['persist' => true, 'data' => ['title' => 'Foo']])->toArray(true);
        $this->assertNotNull($result['data']['createArticle']['id']);
        $id = $result['data']['createArticle']['id'];
        $this->assertEquals(['data' => [
            'createArticle' => [
                'id' => $id,
                'title' => 'Foo'
            ],
        ]], $result);

        // Find all articles
        $result = $schemaManager->execute($find)->toArray(true);
        $this->assertEquals(['data' => [
            'findArticle' => [
                'total' => 1,
                'result' => [
                    [
                        'id' => $id,
                        'title' => 'Foo'
                    ]
                ]
            ],
        ]], $result);

        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => [
                'id' => $id,
                'title' => 'Foo'
            ]
        ]], $result);

        // Update article
        $result = $schemaManager->execute($update, ['persist' => true, 'id' => $id, 'data' => ['title' => 'Baa']])->toArray(true);
        $this->assertEquals(['data' => [
            'updateArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);


        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);


        // Delete article by id without persist
        $result = $schemaManager->execute($delete, ['id' => $id, 'persist' => false])->toArray(true);
        $this->assertEquals(['data' => [
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);

        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);

        // Delete article by id with persist
        $result = $schemaManager->execute($delete, ['id' => $id, 'persist' => true])->toArray(true);
        $this->assertEquals(['data' => [
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);

        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => null
        ]], $result);


        // Recover article by id without persist
        $result = $schemaManager->execute($recover, ['id' => $id, 'persist' => false])->toArray(true);
        $this->assertEquals(['data' => [
            'recoverArticle' => null
        ]], $result);

        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => null
        ]], $result);



        // Recover article by id with persist
        $result = $schemaManager->execute($recover, ['id' => $id, 'persist' => true])->toArray(true);
        $this->assertEquals(['data' => [
            'recoverArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);

        // Get article by id
        $result = $schemaManager->execute($get, ['id' => $id])->toArray(true);
        $this->assertEquals(['data' => [
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);


        // Delete article by id with persist
        $result = $schemaManager->execute($delete, ['id' => $id, 'persist' => true])->toArray(true);
        $this->assertEquals(['data' => [
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);


        // Permanently delete article by id with persist
        $result = $schemaManager->execute($delete, ['id' => $id, 'persist' => true])->toArray(true);
        $this->assertEquals(['data' => [
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ]], $result);

        // Recover will not change anything here.
        $result = $schemaManager->execute($recover, ['id' => $id, 'persist' => true])->toArray(true);
        $this->assertEquals(['data' => [
            'recoverArticle' => null
        ]], $result);

    }

    public function setUp() {
        static::bootKernel();
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', ''));
    }

    /**
     * @param string $schema
     *
     * @return \GraphQL\Language\AST\DocumentNode
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    protected function buildSchema(string $schema = '') : DocumentNode {

        $schemaManager = static::$container->get(SchemaManager::class);
        $domainManager = static::$container->get(DomainManager::class);
        $domain = $domainManager->current();

        $domainManager->setCurrentDomain(new Domain(
            'test',
            $domain->getContentManager(),
            $domain->getUserManager(),
            array_merge($domain->getSchema(), [$schema])
        ));

        return $schemaManager->buildCacheableSchema();
    }

    /**
     * @param string $schema
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    protected function assertValidSchema(string $schema = '') : void {
        BuildSchema::build($this->buildSchema($schema))->assertValid();
    }
}
