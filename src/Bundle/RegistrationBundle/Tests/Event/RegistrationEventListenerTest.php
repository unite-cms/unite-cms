<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:36
 */

namespace UniteCMS\RegistrationBundle\Tests\Event;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Event\RegistrationEvent;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class RegistrationEventListenerTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    protected static function bootKernel(array $options = array())
    {
        $options['environment'] = 'test_registration';
        return parent::bootKernel($options);
    }

    public function setUp()
    {
        parent::setUp();

        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        $org = new Organization();
        $org->setIdentifier('taken')->setTitle('Taken');
        $this->em->persist($org);
        $this->em->flush();
    }

    public function testInvitationRegistrationEvents() {

        $subscriberMock = new class implements EventSubscriberInterface
        {
            public $events = [];

            public static function getSubscribedEvents()
            {
                return [
                    RegistrationEvent::REGISTRATION_COMPLETE => 'onComplete',
                    RegistrationEvent::REGISTRATION_SUCCESS => 'onSuccess',
                    RegistrationEvent::REGISTRATION_FAILURE => 'onFailure',
                ];
            }

            public function onComplete(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_COMPLETE, 'event' => $event];
            }

            public function onSuccess(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_SUCCESS, 'event' => $event];
            }

            public function onFailure(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_FAILURE, 'event' => $event];
            }
        };
        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($subscriberMock);
        $this->client->disableReboot();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate(
            'unitecms_registration_registration_registration',
            [],
            Router::ABSOLUTE_URL
        ));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['registration[name]'] = 'This is me';
        $form['registration[email]'] = 'me@example.com';
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationTitle]'] = 'New Organization';
        $form['registration[organizationIdentifier]'] = 'neworg';

        // Submitting valid data should result in an success and complete invitation action.
        $this->client->submit($form);

        $this->assertCount(2, $subscriberMock->events);
        $this->assertEquals(RegistrationEvent::REGISTRATION_SUCCESS, $subscriberMock->events[0]['type']);
        $this->assertEquals(RegistrationEvent::REGISTRATION_COMPLETE, $subscriberMock->events[1]['type']);
    }

    public function testInvitationRegistrationFailure() {

        $subscriberMock = new class implements EventSubscriberInterface
        {
            public $events = [];

            public static function getSubscribedEvents()
            {
                return [
                    RegistrationEvent::REGISTRATION_COMPLETE => 'onComplete',
                    RegistrationEvent::REGISTRATION_SUCCESS => 'onSuccess',
                    RegistrationEvent::REGISTRATION_FAILURE => 'onFailure',
                ];
            }

            public function onComplete(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_COMPLETE, 'event' => $event];
            }

            public function onSuccess(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_SUCCESS, 'event' => $event];
            }

            public function onFailure(RegistrationEvent $event)
            {
                $this->events[] = ['type' => RegistrationEvent::REGISTRATION_FAILURE, 'event' => $event];
            }
        };
        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($subscriberMock);
        $this->client->disableReboot();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate(
            'unitecms_registration_registration_registration',
            [],
            Router::ABSOLUTE_URL
        ));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['registration[name]'] = 'This is me';
        $form['registration[email]'] = 'me@example.com';
        $form['registration[password][first]'] = 'password';
        $form['registration[password][second]'] = 'password';
        $form['registration[organizationTitle]'] = 'New Organization';
        $form['registration[organizationIdentifier]'] = 'taken';

        // Submitting valid data should result in an success and complete invitation action.
        $this->client->submit($form);

        $this->assertCount(1, $subscriberMock->events);
        $this->assertEquals(RegistrationEvent::REGISTRATION_FAILURE, $subscriberMock->events[0]['type']);
    }

}