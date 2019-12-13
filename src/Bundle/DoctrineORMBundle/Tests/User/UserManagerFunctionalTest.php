<?php


namespace UniteCMS\DoctrineORMBundle\Tests\Content;

use Symfony\Component\Validator\Constraint;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Tests\DatabaseAwareTestCase;

class UserManagerFunctionalTest extends DatabaseAwareTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->buildSchema('
            type Foo implements UniteUser @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true") {
                id: ID
                _meta: UniteContentMeta
                username: String @textField
            }
        ');
    }

    public function testContentManagerCRUD() {

        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getUserManager();
        $validator = static::$container->get('validator');
        $criteria = new ContentCriteria();
        $this->assertInstanceOf(ContentManager::class, $manager);

        // CREATE
        $user = $manager->create($domain, 'Foo');
        $this->assertTrue($user->isNew());
        $this->assertNull($user->getId());
        $this->assertEquals('Foo', $user->getType());

        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
        $this->assertNotNull($user->getId());
        $this->assertEquals(1, $manager->find($domain, 'Foo', $criteria)->getTotal());

        // UPDATE
        $manager->update($domain, $user, [
            'baa' => new FieldData('fuu'),
            'username' => new FieldData('foo@baa.com'),
            'password' =>  new SensitiveFieldData('faa'),
        ]);
        $this->assertCount(3, $user->getData());
        $this->assertEquals('fuu', $user->getFieldData('baa'));
        $this->assertEquals('faa', $user->getFieldData('password'));
        $this->assertEquals(null, $user->getPassword());
        $this->assertEquals('foo@baa.com', $user->getUsername());
        $manager->flush($domain);

        // FIND BY USERNAME
        $this->assertNull($manager->findByUsername($domain, 'la'));
        $this->assertEquals($user, $manager->findByUsername($domain, 'foo@baa.com'));

        // DELETE
        $manager->delete($domain, $user);
        $this->assertNotNull($user->getDeleted());
        $manager->flush($domain);

        // RECOVER
        $manager->recover($domain, $user);
        $this->assertNull($user->getDeleted());
        $manager->flush($domain);

        // PERMANENT DELETE
        $this->assertCount(0, $validator->validate($user, null, [Constraint::DEFAULT_GROUP, ContentEvent::PERMANENT_DELETE]));
        $manager->permanentDelete($domain, $user);
        $this->assertEquals(1, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
    }
}
