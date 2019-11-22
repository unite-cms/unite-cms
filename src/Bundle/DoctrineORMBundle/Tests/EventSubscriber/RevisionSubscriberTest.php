<?php


namespace UniteCMS\DoctrineORMBundle\Tests\EventSubscriber;

use DateTime;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Event\ContentEventBefore;
use UniteCMS\DoctrineORMBundle\Entity\Content;
use UniteCMS\DoctrineORMBundle\Entity\Revision;
use UniteCMS\DoctrineORMBundle\Tests\DatabaseAwareTestCase;

class RevisionSubscriberTest extends DatabaseAwareTestCase
{
    public function testRevisionsCRUD() {

        $dispatcher = static::$container->get('event_dispatcher');

        // CREATE
        $content = new Content('Foo');
        $this->em->persist($content);
        $this->em->flush();
        $dispatcher->dispatch(new ContentEventAfter($content), ContentEventAfter::CREATE);

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(1, $revisions);
        $this->assertEquals(ContentEvent::CREATE, $revisions[0]->getOperation());
        $this->assertNotNull($revisions[0]->getEntityId());
        $this->assertEquals($content->getId(), $revisions[0]->getEntityId());
        $this->assertEquals($content->getType(), $revisions[0]->getEntityType());
        $this->assertEquals('anon', $revisions[0]->getOperatorName());

        // UPDATE
        $content->setData(['baa' => 'lu']);
        $this->em->flush();
        $dispatcher->dispatch(new ContentEventAfter($content), ContentEventAfter::UPDATE);

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(2, $revisions);
        $this->assertEquals(ContentEvent::UPDATE, $revisions[0]->getOperation());
        $this->assertEquals($content->getData(), $revisions[0]->getData());


        // RECOVER
        $content->setData(['baa' => 'a']);
        $this->em->flush();
        $dispatcher->dispatch(new ContentEventAfter($content), ContentEventAfter::RECOVER);

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(3, $revisions);
        $this->assertEquals(ContentEvent::RECOVER, $revisions[0]->getOperation());
        $this->assertEquals($content->getData(), $revisions[0]->getData());

        // DELETE
        $content->setDeleted(new DateTime());
        $this->em->flush();
        $dispatcher->dispatch(new ContentEventAfter($content), ContentEventAfter::DELETE);

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(4, $revisions);
        $this->assertEquals(ContentEvent::DELETE, $revisions[0]->getOperation());
        $this->assertEquals($content->getData(), $revisions[0]->getData());

        // RECOVER
        $content->setDeleted(null);
        $this->em->flush();
        $dispatcher->dispatch(new ContentEventAfter($content), ContentEventAfter::RECOVER);

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(5, $revisions);
        $this->assertEquals(ContentEvent::RECOVER, $revisions[0]->getOperation());
        $this->assertEquals($content->getData(), $revisions[0]->getData());
        $this->assertEquals($content->getData(), $revisions[0]->getData());

        // PERMANENT_DELETE
        $this->em->remove($content);
        $dispatcher->dispatch(new ContentEventBefore($content), ContentEventBefore::PERMANENT_DELETE);
        $this->em->flush();

        /**
         * @var Revision[] $revisions
         */
        $revisions = $this->em->getRepository(Revision::class)->findBy([], ['version' => 'DESC']);
        $this->assertCount(0, $revisions);
    }
}
