<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:36
 */

namespace App\Bundle\CoreBundle\Tests\Event;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainInvitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Event\RegistrationEvent;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class RegistrationEventListenerTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var DomainInvitation $domainInvitation
     */
    private $domainInvitation;

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');
        $domain = new Domain();
        $domain->setIdentifier('domain')->setTitle('Domain')->setOrganization($org);

        $this->domainInvitation = new DomainInvitation();
        $this->domainInvitation
            ->setEmail('test@example.com')
            ->setToken('token')
            ->setDomainMemberType($domain->getDomainMemberTypes()->first())
            ->setRequestedAt(new \DateTime('now'));

        $this->container->get('doctrine.orm.entity_manager')->persist($org);
        $this->container->get('doctrine.orm.entity_manager')->persist($domain);
        $this->container->get('doctrine.orm.entity_manager')->persist($this->domainInvitation);
        $this->container->get('doctrine.orm.entity_manager')->flush();
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

        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $this->domainInvitation->getToken()]));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['invitation_registration[name]'] = 'foo';
        $form['invitation_registration[email]'] = 'baa@baa.com';
        $form['invitation_registration[password][first]'] = 'password';
        $form['invitation_registration[password][second]'] = 'password';

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

        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $this->domainInvitation->getToken()]));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['invitation_registration[name]'] = 'foo';
        $form['invitation_registration[email]'] = 'baa@baa.com';
        $form['invitation_registration[password][first]'] = 'password';
        $form['invitation_registration[password][second]'] = 'password';

        // Manipulate domain org, so user validation will fail.
        $org2 = new Organization();
        $org2->setIdentifier('org2')->setTitle('org2')->setId(2);
        $this->domainInvitation->getDomainMemberType()->getDomain()->setOrganization($org2);
        $org2->setDomains([]);

        // Submitting valid data should result in an success and complete invitation action.
        $this->client->submit($form);

        $this->assertCount(1, $subscriberMock->events);
        $this->assertEquals(RegistrationEvent::REGISTRATION_FAILURE, $subscriberMock->events[0]['type']);
    }

}