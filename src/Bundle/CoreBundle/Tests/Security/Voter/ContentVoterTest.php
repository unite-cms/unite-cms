<?php


namespace UniteCMS\CoreBundle\Tests\Security\Voter;

use GraphQL\Error\Error;
use GraphQL\Utils\BuildSchema;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Tests\Mock\TestUser;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class ContentVoterTest extends SchemaAwareTestCase
{
    const SCHEMA = '
        type Article implements UniteContent 
            @access(
                query: "user.getFieldData(\'query\') == \'query\'", 
                mutation: "user.getFieldData(\'mutation\') == \'mutation\'", 
                create: "subject.getFieldData(\'title\') == \'create\'", 
                read: "subject.getFieldData(\'title\') == \'read\'", 
                update: "subject.getFieldData(\'title\') == \'update\'", 
                delete: "subject.getFieldData(\'title\') == \'delete\'"
            ) 
        {
            id: ID
            _meta: UniteContentMeta!
            title: String @textField
        }
    ';

    protected $user;

    public function setUp()
    {
        static::bootKernel();
        $this->user = new TestUser('User');
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', $this->user));
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
}
