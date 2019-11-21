<?php


namespace UniteCMS\CoreBundle\Tests\Security\Voter;

use DateTime;
use GraphQL\Error\Error;
use GraphQL\Utils\BuildSchema;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Tests\Mock\TestUser;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class ContentVoterTest extends SchemaAwareTestCase
{
    const SCHEMA = '
        type Article implements UniteContent 
            @access(
                query: "user.get(\'query\') == \'query\'", 
                mutation: "user.get(\'mutation\') == \'mutation\'", 
                create: "content.get(\'title\') == \'create\'", 
                read: "content.get(\'title\') == \'read\'", 
                update: "content.get(\'title\') == \'update\'", 
                delete: "content.get(\'title\') == \'delete\'"
            ) 
        {
            id: ID
            _meta: UniteContentMeta
            title: String @textField
        }
    ';

    protected $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = new TestUser('User');
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', $this->user));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\ConstraintViolationsException
     * @expectedExceptionMessage contentTypes[Article].permissions[query]: This value should not be blank.
     */
    public function testEmptyAccessProperties() {
        $this->buildSchema('
            type Article implements UniteContent @access(query: "", mutation: "true", read: "true", update: "true") {
                id: ID
                _meta: UniteContentMeta
                title: String @textField
            }
        ');
    }

    public function testValidAccessProperty() {
        $schema = BuildSchema::build($this->buildSchema('
            type Article implements UniteContent @access(query: "true", mutation: "true", read: "true", update: "true") {
                id: ID
                _meta: UniteContentMeta
                title: String @textField
            }
        '));
        $this->assertCount(0, $schema->validate());
    }

    public function testSchemaLevelNoAccess() {
        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();

        try { $this->assertFalse($schema->hasType('Article')); } catch (Error $e) {}
        try { $this->assertFalse($schema->hasType('ArticleInput'));} catch (Error $e) {}

        $this->assertFalse(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::QUERY, $domain->getContentTypeManager()->getContentType('Article'))
        );

        $this->assertFalse(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::MUTATION, $domain->getContentTypeManager()->getContentType('Article'))
        );
    }

    public function testSchemaLevelQueryAccess() {

        $this->user->setData([
            'query' => new FieldData('query')
        ]);

        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();

        try { $this->assertTrue($schema->hasType('Article')); } catch (Error $e) {}
        try { $this->assertFalse($schema->hasType('ArticleInput'));} catch (Error $e) {}

        $this->assertTrue(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::QUERY, $domain->getContentTypeManager()->getContentType('Article'))
        );

        $this->assertFalse(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::MUTATION, $domain->getContentTypeManager()->getContentType('Article'))
        );
    }

    public function testSchemaLevelMutationOnlyAccess() {

        $this->user->setData([
            'mutation' => new FieldData('mutation')
        ]);

        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();

        try { $this->assertTrue($schema->hasType('Article')); } catch (Error $e) {}
        try { $this->assertTrue($schema->hasType('ArticleInput'));} catch (Error $e) {}

        $this->assertFalse(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::QUERY, $domain->getContentTypeManager()->getContentType('Article'))
        );

        $this->assertTrue(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::MUTATION, $domain->getContentTypeManager()->getContentType('Article'))
        );
    }

    public function testSchemaLevelFullAccess() {

        $this->user->setData([
            'query' => new FieldData('query'),
            'mutation' => new FieldData('mutation'),
        ]);

        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();

        try { $this->assertTrue($schema->hasType('Article')); } catch (Error $e) {}
        try { $this->assertTrue($schema->hasType('ArticleInput'));} catch (Error $e) {}

        $this->assertTrue(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::QUERY, $domain->getContentTypeManager()->getContentType('Article'))
        );

        $this->assertTrue(
            static::$container
                ->get('security.authorization_checker')
                ->isGranted(ContentVoter::MUTATION, $domain->getContentTypeManager()->getContentType('Article'))
        );
    }

    public function testContentLevelAccess() {

        $this->user->setData([
            'query' => new FieldData('query'),
            'mutation' => new FieldData('mutation'),
        ]);

        $this->buildSchema(static::SCHEMA);
        $domain = static::$container->get(DomainManager::class)->current();
        $checker = static::$container->get('security.authorization_checker');

        $content = $domain->getContentManager()->create($domain, 'Article');

        $this->assertFalse($checker->isGranted(ContentVoter::CREATE, $content));
        $content->setData(['title' => new FieldData('create')]);
        $this->assertTrue($checker->isGranted(ContentVoter::CREATE, $content));

        $this->assertFalse($checker->isGranted(ContentVoter::READ, $content));
        $content->setData(['title' => new FieldData('read')]);
        $this->assertTrue($checker->isGranted(ContentVoter::READ, $content));

        $this->assertFalse($checker->isGranted(ContentVoter::UPDATE, $content));
        $content->setData(['title' => new FieldData('update')]);
        $this->assertTrue($checker->isGranted(ContentVoter::UPDATE, $content));

        $this->assertFalse($checker->isGranted(ContentVoter::DELETE, $content));
        $content->setData(['title' => new FieldData('delete')]);
        $this->assertTrue($checker->isGranted(ContentVoter::DELETE, $content));
    }

    public function testAPIAccess() {

        $this->user->setData([
            'query' => new FieldData('query'),
            'mutation' => new FieldData('mutation'),
        ]);

        $domain = static::$container->get(DomainManager::class)->current();
        $this->buildSchema(static::SCHEMA);
        $contentManager = $domain->getContentManager();
        $content = $domain->getContentManager()->create($domain, 'Article');
        $contentManager->flush($domain);

        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to create content of type "Article".',
                'path' => ['createArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($data: ArticleInput!) { createArticle(persist: true, data: $data) { id, title } }', [
            'data' => [
                'title' => 'foo',
            ],
        ], false);

        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to update content of type "Article".',
                'path' => ['updateArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($id: ID!, $data: ArticleInput!) { updateArticle(id: $id, persist: true, data: $data) { id, title } }', [
            'id' => $content->getId(),
            'data' => [
                'title' => 'foo',
            ],
        ], false);

        $content->setData([
            'title' => new FieldData('update')
        ]);

        $this->assertGraphQL([
            'updateArticle' => [
                'id' => $content->getId(),
                'title' => 'read'
            ]
        ], 'mutation($id: ID!, $data: ArticleInput!) { updateArticle(id: $id, persist: true, data: $data) { id, title } }', [
            'id' => $content->getId(),
            'data' => [
                'title' => 'read',
            ],
        ]);

        $this->assertGraphQL([
            'findArticle' => [
                'total' => 1,
                'result' => [
                    [
                        'id' => $content->getId(),
                        'title' => 'read'
                    ]
                ],
            ]
        ], 'query { findArticle { total, result { id, title } } }');

        $this->assertGraphQL([
            'getArticle' => [
                'id' => $content->getId(),
                'title' => 'read'
            ]
        ], 'query($id: ID!) { getArticle(id: $id) { id, title } }', [
            'id' => $content->getId(),
        ]);

        $content->setData([
            'title' => new FieldData('foo')
        ]);

        $this->assertGraphQL([
            'findArticle' => [
                'total' => 1,
                'result' => [],
            ]
        ], 'query { findArticle { total, result { id, title } } }');

        $this->assertGraphQL([
            'getArticle' => null,
        ], 'query($id: ID!) { getArticle(id: $id) { id, title } }', [
            'id' => $content->getId(),
        ]);


        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to delete content of type "Article".',
                'path' => ['deleteArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($id: ID!) { deleteArticle(id: $id, persist: true) { id, title } }', [
            'id' => $content->getId(),
        ], false);

        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to update content of type "Article".',
                'path' => ['revertArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($id: ID!) { revertArticle(id: $id, version: 1, persist: true) { id, title } }', [
            'id' => $content->getId(),
        ], false);


        $content->setDeleted(new DateTime());

        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to delete content of type "Article".',
                'path' => ['deleteArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($id: ID!) { deleteArticle(id: $id, persist: true) { id, title } }', [
            'id' => $content->getId(),
        ], false);

        $this->assertGraphQL([
            [
                'message' => 'You are not allowed to update content of type "Article".',
                'path' => ['recoverArticle'],
                'extensions' => [
                    'category' => 'access',
                ],
            ]
        ], 'mutation($id: ID!) { recoverArticle(id: $id, persist: true) { id, title } }', [
            'id' => $content->getId(),
        ], false);

    }
}
