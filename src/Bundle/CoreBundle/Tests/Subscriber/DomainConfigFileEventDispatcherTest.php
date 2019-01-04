<?php

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UniteCMS\CoreBundle\Event\DomainConfigFileEvent;

class DomainConfigFileEventDispatcherTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var Organization $organization
     */
    private $organization;

    private $subscriberMock;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1_org1');
        $this->em->persist($this->organization);
        $this->em->flush();
        $this->em->refresh($this->organization);

        $this->subscriberMock = new class implements EventSubscriberInterface
        {
            public $events = [];

            public static function getSubscribedEvents()
            {
                return [
                    DomainConfigFileEvent::DOMAIN_CONFIG_FILE_CREATE => 'onCreate',
                    DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE => 'onUpdate',
                    DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE => 'onDelete',
                ];
            }

            public function onCreate(DomainConfigFileEvent $event) {
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_CREATE, 'event' => $event];
            }

            public function onUpdate(DomainConfigFileEvent $event) {
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE, 'event' => $event];
            }

            public function onDelete(DomainConfigFileEvent $event){
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE, 'event' => $event];
            }
        };

        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($this->subscriberMock);
        $this->client->disableReboot();
    }

    public function testDomainConfigTriggerEvents()
    {
        // Create test domain.
        $this->subscriberMock->events = [];
        $domain = new Domain();
        $domain
            ->setIdentifier('test')
            ->setTitle('Test1')
            ->setOrganization($this->organization);
    
        $this->em->persist($domain);
        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertCount(1, $this->subscriberMock->events);
        $this->assertEquals('Test1', $this->subscriberMock->events[0]['event']->getDomain()->getTitle());
        $this->assertEquals(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_CREATE, $this->subscriberMock->events[0]['type']);

        // Update domain
        $this->subscriberMock->events = [];
        $domain->setTitle('Test2');
        $this->em->persist($domain);
        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertCount(1, $this->subscriberMock->events);
        $this->assertEquals('Test2', $this->subscriberMock->events[0]['event']->getDomain()->getTitle());
        $this->assertEquals(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE, $this->subscriberMock->events[0]['type']);

        // Delete domain
        $this->subscriberMock->events = [];
        $this->em->remove($domain);
        $this->em->flush($domain);
        $this->assertCount(1, $this->subscriberMock->events);
        $this->assertEquals('Test2', $this->subscriberMock->events[0]['event']->getDomain()->getTitle());
        $this->assertEquals(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE, $this->subscriberMock->events[0]['type']);
    }
}
