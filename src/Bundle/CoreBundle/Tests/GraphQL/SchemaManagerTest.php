<?php


namespace UniteCMS\CoreBundle\Tests\GraphQL;

use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class SchemaManagerTest extends SchemaAwareTestCase
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
                _meta: UniteContentMeta
                title: String @textField(type: "text")
            }
        ');
        $this->addToAssertionCount(1);
    }

    public function testBasicContentCrud() {

        $this->buildSchema('
            type Article implements UniteContent @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true", permanent_delete: "true") {
                id: ID
                _meta: UniteContentMeta
                title: String @textField
            }
        ');

        $create = 'mutation($data: ArticleInput!, $persist: Boolean!) { createArticle(data: $data, persist: $persist) { id, title }}';
        $update = 'mutation($id: ID!, $data: ArticleInput!, $persist: Boolean!) { updateArticle(id: $id, data: $data, persist: $persist) { id, title }}';
        $delete = 'mutation($id: ID!, $persist: Boolean!) { deleteArticle(id: $id, persist: $persist) { id, title }}';
        $permanent_delete = 'mutation($id: ID!, $force: Boolean, $persist: Boolean!) { permanent_deleteArticle(id: $id, persist: $persist, force: $force) { id, title }}';
        $recover = 'mutation($id: ID!, $persist: Boolean!) { recoverArticle(id: $id, persist: $persist) { id, title }}';
        $get = 'query($id: ID!) { getArticle(id: $id) { id, title }}';
        $find = 'query { findArticle { total, result { id, title }}}';

        // Create article without persist
        $this->assertGraphQL([
            'createArticle' => [
                'id' => null,
                'title' => 'Foo'
            ],
        ], $create, ['persist' => false, 'data' => ['title' => 'Foo']]);


        // Create article with persist
        list($id) = $this->assertGraphQL([
            'createArticle' => [
                'id' => '{id}',
                'title' => 'Foo'
            ],
        ], $create, ['persist' => true, 'data' => ['title' => 'Foo']]);

        // Find all articles
        $this->assertGraphQL([
            'findArticle' => [
                'total' => 1,
                'result' => [
                    [
                        'id' => $id,
                        'title' => 'Foo'
                    ]
                ]
            ],
        ], $find);

        // Get article by id
        $this->assertGraphQL([
            'getArticle' => [
                'id' => $id,
                'title' => 'Foo'
            ],
        ], $get, ['id' => $id]);

        // Update article
        $this->assertGraphQL([
            'updateArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $update, ['persist' => true, 'id' => $id, 'data' => ['title' => 'Baa']]);


        // Get article by id
        $this->assertGraphQL([
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $get, ['id' => $id]);


        // Delete article by id without persist
        $this->assertGraphQL([
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $delete, ['id' => $id, 'persist' => false]);

        // Get article by id
        $this->assertGraphQL([
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $get, ['id' => $id]);

        // Delete article by id with persist
        $this->assertGraphQL([
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $delete, ['id' => $id, 'persist' => true]);

        // Get article by id
        $this->assertGraphQL([
            'getArticle' => null,
        ], $get, ['id' => $id]);


        // Recover article by id without persist
        $this->assertGraphQL([
            'recoverArticle' => null
        ], $recover, ['id' => $id, 'persist' => false]);

        // Get article by id
        $this->assertGraphQL([
            'getArticle' => null,
        ], $get, ['id' => $id]);



        // Recover article by id with persist
        $this->assertGraphQL([
            'recoverArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ], $recover, ['id' => $id, 'persist' => true]);

        // Get article by id
        $this->assertGraphQL([
            'getArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ]
        ], $get, ['id' => $id]);

        // Permanently delete article by id with persist, but without force.
        // Since this article was not deleted before, it will throw an error.
        $this->assertGraphQL([
            [
                'message' => 'You can only permanent delete content if it is already deleted or if you set the "force" argument to true.',
                'path' => [
                    'permanent_deleteArticle'
                ],
                'extensions' => [
                    'category' => 'user',
                ],
            ]
        ], $permanent_delete, ['id' => $id, 'persist' => true], false);

        // Delete article by id with persist
        $this->assertGraphQL([
            'deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $delete, ['id' => $id, 'persist' => true]);

        // Permanently delete article by id with persist
        $this->assertGraphQL([
            'permanent_deleteArticle' => [
                'id' => $id,
                'title' => 'Baa'
            ],
        ], $permanent_delete, ['id' => $id, 'persist' => true]);

        // Recover will produce content not found error.
        $this->assertGraphQL([
            [
                'message' => sprintf('Content with id "%s" was not found.', $id),
                'path' => [
                    'recoverArticle'
                ],
                'extensions' => [
                    'category' => 'content',
                ],
            ]
        ], $recover, ['id' => $id, 'persist' => true], false);

    }
}
