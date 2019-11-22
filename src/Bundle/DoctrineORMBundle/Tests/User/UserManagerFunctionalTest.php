<?php


namespace UniteCMS\DoctrineORMBundle\Tests\Content;

use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Tests\DatabaseAwareTestCase;

class UserManagerFunctionalTest extends DatabaseAwareTestCase
{
    public function testContentManagerCRUD() {

        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getUserManager();
        $criteria = new ContentCriteria();
        $this->assertInstanceOf(ContentManager::class, $manager);

        // CREATE
        $user = $manager->create($domain, 'Foo');
        $this->assertTrue($user->isNew());
        $this->assertNotNull($user->getId());
        $this->assertEquals('Foo', $user->getType());

        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
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
        $this->assertNull($manager->findByUsername($domain, 'Foo', 'la'));
        $this->assertNull($manager->findByUsername($domain, 'Baa', 'foo@baa.com'));
        $this->assertEquals($user, $manager->findByUsername($domain, 'Foo', 'foo@baa.com'));

        // DELETE
        $manager->delete($domain, $user);
        $this->assertNotNull($user->getDeleted());
        $manager->flush($domain);

        // RECOVER
        $manager->recover($domain, $user);
        $this->assertNull($user->getDeleted());
        $manager->flush($domain);

        // PERMANENT DELETE
        $manager->permanentDelete($domain, $user);
        $this->assertEquals(1, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
    }
}
