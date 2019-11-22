<?php


namespace UniteCMS\DoctrineORMBundle\Tests\Content;

use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Tests\DatabaseAwareTestCase;

class ContentManagerFunctionalTest extends DatabaseAwareTestCase
{
    public function testContentManagerCRUD() {

        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getContentManager();
        $criteria = new ContentCriteria();
        $this->assertInstanceOf(ContentManager::class, $manager);

        // CREATE
        $content = $manager->create($domain, 'Foo');
        $this->assertTrue($content->isNew());
        $this->assertNotNull($content->getId());
        $this->assertEquals('Foo', $content->getType());

        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
        $this->assertEquals(1, $manager->find($domain, 'Foo', $criteria)->getTotal());

        // UPDATE
        $manager->update($domain, $content, ['baa' => new FieldData('fuu')]);
        $this->assertEquals('fuu', $content->getFieldData('baa'));
        $manager->flush($domain);

        // DELETE
        $manager->delete($domain, $content);
        $this->assertNotNull($content->getDeleted());
        $manager->flush($domain);

        // RECOVER
        $manager->recover($domain, $content);
        $this->assertNull($content->getDeleted());
        $manager->flush($domain);

        // PERMANENT DELETE
        $manager->permanentDelete($domain, $content);
        $this->assertEquals(1, $manager->find($domain, 'Foo', $criteria)->getTotal());
        $manager->flush($domain);
        $this->assertEquals(0, $manager->find($domain, 'Foo', $criteria)->getTotal());
    }
}
