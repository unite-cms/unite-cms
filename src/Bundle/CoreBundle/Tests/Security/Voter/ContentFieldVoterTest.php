<?php


namespace UniteCMS\CoreBundle\Tests\Security\Voter;

use GraphQL\Utils\BuildSchema;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use UniteCMS\CoreBundle\Content\ContentField;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Security\Voter\ContentFieldVoter;
use UniteCMS\CoreBundle\Tests\Mock\TestUser;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class ContentFieldVoterTest extends SchemaAwareTestCase
{
    const SCHEMA = '
        type Article implements UniteContent 
            @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") 
        {
            id: ID
            _meta: UniteContentMeta!
            title: String @textField @access(
                mutation: "user.get(\'mutation\') == \'mutation\'"  
                read: "content.get(\'title\') == \'access\'"
                update: "content.get(\'title\') == \'access\'"
            )
            foo: String @textField @access(mutation: "true")
        }
    ';

    protected $user;

    public function setUp()
    {
        static::bootKernel();
        $this->user = new TestUser('User');
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', $this->user));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\ConstraintViolationsException
     * @expectedExceptionMessage contentTypes[Article].fields[title].permissions[query]: This field was not expected., contentTypes[Article].fields[title].permissions[create]: This field was not expected., contentTypes[Article].fields[title].permissions[delete]: This field was not expected.
     */
    public function testInvalidAccessProperties() {
        $this->buildSchema('
            type Article implements UniteContent {
                id: ID
                _meta: UniteContentMeta!
                title: String @textField
                    @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true")
            }
        ');
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\ConstraintViolationsException
     * @expectedExceptionMessage contentTypes[Article].fields[title].permissions[mutation]: This value should not be blank.
     */
    public function testEmptyAccessProperties() {
        $this->buildSchema('
            type Article implements UniteContent {
                id: ID
                _meta: UniteContentMeta!
                title: String @textField
                    @access(mutation: "", read: "true", update: "true")
            }
        ');
    }

    public function testValidAccessProperty() {
        $schema = BuildSchema::build($this->buildSchema('
            type Article implements UniteContent {
                id: ID
                _meta: UniteContentMeta!
                title: String @textField
                    @access(mutation: "true", read: "true", update: "true")
            }
        '));
        $this->assertCount(0, $schema->validate());
    }

    public function testSchemaLevelNoAccess() {
        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();
        $field = $domain->getContentTypeManager()->getContentType('Article')->getField('title');

        $this->assertTrue($schema->hasType('ArticleInput'));
        $this->assertFalse(static::$container->get('security.authorization_checker')->isGranted(ContentFieldVoter::MUTATION, $field));
        $this->assertArrayNotHasKey('title', $schema->getType('ArticleInput')->config['fields']());
    }

    public function testSchemaLevelFullAccess() {

        $this->user->setData([
            'mutation' => new FieldData('mutation'),
        ]);

        $schema = BuildSchema::build($this->buildSchema(static::SCHEMA));
        $domain = static::$container->get(DomainManager::class)->current();
        $field = $domain->getContentTypeManager()->getContentType('Article')->getField('title');

        $this->assertTrue($schema->hasType('ArticleInput'));
        $this->assertTrue(static::$container->get('security.authorization_checker')->isGranted(ContentFieldVoter::MUTATION, $field));
        $this->assertArrayHasKey('title', $schema->getType('ArticleInput')->config['fields']());
    }

    public function testContentLevelAccess() {

        $this->user->setData([
            'mutation' => new FieldData('mutation'),
        ]);

        $this->buildSchema(static::SCHEMA);
        $domain = static::$container->get(DomainManager::class)->current();
        $checker = static::$container->get('security.authorization_checker');

        $content = $domain->getContentManager()->create($domain, 'Article');
        $contentField = new ContentField($content, 'title');

        $this->assertFalse($checker->isGranted(ContentFieldVoter::READ, $contentField));
        $content->setData(['title' => new FieldData('access')]);
        $this->assertTrue($checker->isGranted(ContentFieldVoter::READ, $contentField));

        $content->setData(['title' => new FieldData('foo')]);

        $this->assertFalse($checker->isGranted(ContentFieldVoter::UPDATE, $contentField));
        $content->setData(['title' => new FieldData('access')]);
        $this->assertTrue($checker->isGranted(ContentFieldVoter::UPDATE, $contentField));
    }

    public function testAPIReadAccessCheck() {

        $domain = static::$container->get(DomainManager::class)->current();
        $this->buildSchema(static::SCHEMA);
        $contentManager = $domain->getContentManager();

        // Create test content
        $content = $contentManager->create($domain, 'Article');
        $content->setData(['title' => new FieldData('foo')]);
        $contentManager->persist($domain, $content, ContentEvent::CREATE);


        $this->assertGraphQL([
            'findArticle' => [
                'result' => [
                    [
                        'id' => $content->getId(),
                        'title' => null,
                    ]
                ],
            ],
        ], 'query { findArticle { result { id, title } } }');

        $content->setData([
            'title' => new FieldData('access'),
        ]);

        $this->assertGraphQL([
            'findArticle' => [
                'result' => [
                    [
                        'id' => $content->getId(),
                        'title' => 'access',
                    ]
                ],
            ],
        ], 'query { findArticle { result { id, title } } }');

    }

    public function testAPICreateAccessCheck() {

        $this->user->setData([
            'mutation' => new FieldData('mutation'),
        ]);

        $domain = static::$container->get(DomainManager::class)->current();
        $this->buildSchema(static::SCHEMA);
        $contentManager = $domain->getContentManager();

        list($id) = $this->assertGraphQL([
            'createArticle' => [
                'id' => '{id}',
                'title' => null,
            ],
        ], 'mutation($data: ArticleInput!) { createArticle(persist: true, data: $data) { id, title } }', [
            'data' => [
                'title' => 'foo',
            ],
        ]);

        $content = $contentManager->get($domain, 'Article', $id);
        $content->setData([
            'title' => new FieldData('foo')
        ]);

        $this->assertGraphQL([
            'updateArticle' => [
                'id' => $id,
            ],
        ], 'mutation($data: ArticleInput!, $id: ID!) { updateArticle(id: $id, persist: true, data: $data) { id } }', [
            'id' => $id,
            'data' => [
                'title' => 'access',
            ],
        ]);

        $this->assertEquals('foo', $content->getFieldData('title')->resolveData());
        $content->setData([
            'title' => new FieldData('access')
        ]);

        $this->assertGraphQL([
            'updateArticle' => [
                'id' => $id,
            ],
        ], 'mutation($data: ArticleInput!, $id: ID!) { updateArticle(id: $id, persist: true, data: $data) { id } }', [
            'id' => $id,
            'data' => [
                'title' => 'baa',
            ],
        ]);

        $this->assertEquals('baa', $content->getFieldData('title')->resolveData());
    }
}
