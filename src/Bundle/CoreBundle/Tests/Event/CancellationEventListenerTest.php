<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:36
 */

namespace UniteCMS\CoreBundle\Tests\Event;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Event\CancellationEvent;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CancellationEventListenerTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $user
     */
    private $user;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        $this->user = new User();
        $this->user->setEmail('test@example.com')->setPassword('password')->setName('Name');

        $this->em->persist($this->user);
        $this->em->flush();

        $token = new UsernamePasswordToken($this->user, null, 'main', $this->user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testCancellationEvents() {

        $subscriberMock = new class implements EventSubscriberInterface
        {
            public $events = [];

            public static function getSubscribedEvents()
            {
                return [
                    CancellationEvent::CANCELLATION_COMPLETE => 'onComplete',
                    CancellationEvent::CANCELLATION_SUCCESS => 'onSuccess',
                    CancellationEvent::CANCELLATION_FAILURE => 'onFailure',
                ];
            }

            public function onComplete(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_COMPLETE, 'event' => $event];
            }

            public function onSuccess(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_SUCCESS, 'event' => $event];
            }

            public function onFailure(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_FAILURE, 'event' => $event];
            }
        };
        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($subscriberMock);
        $this->client->disableReboot();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_update'));

        $form = $crawler->filter('form[name="delete_account"]');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['delete_account[type_email]'] = $this->user->getEmail();

        $this->client->submit($form);

        $this->assertCount(2, $subscriberMock->events);
        $this->assertEquals(CancellationEvent::CANCELLATION_SUCCESS, $subscriberMock->events[0]['type']);
        $this->assertEquals(CancellationEvent::CANCELLATION_COMPLETE, $subscriberMock->events[1]['type']);

        $this->assertEquals('Name', (string)$subscriberMock->events[0]['event']->getUser());
        $this->assertEquals('Name', (string)$subscriberMock->events[1]['event']->getUser());
    }

    public function testCancellationFailure() {

        $subscriberMock = new class implements EventSubscriberInterface
        {
            public $events = [];

            public static function getSubscribedEvents()
            {
                return [
                    CancellationEvent::CANCELLATION_COMPLETE => 'onComplete',
                    CancellationEvent::CANCELLATION_SUCCESS => 'onSuccess',
                    CancellationEvent::CANCELLATION_FAILURE => 'onFailure',
                ];
            }

            public function onComplete(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_COMPLETE, 'event' => $event];
            }

            public function onSuccess(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_SUCCESS, 'event' => $event];
            }

            public function onFailure(CancellationEvent $event)
            {
                $this->events[] = ['type' => CancellationEvent::CANCELLATION_FAILURE, 'event' => $event];
            }
        };
        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($subscriberMock);
        $this->client->disableReboot();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_update'));

        $form = $crawler->filter('form[name="delete_account"]');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['delete_account[type_email]'] = $this->user->getEmail();

        // Make this user an org admin, so he_she cannot be deleted.
        $org = new Organization();
        $org->setIdentifier('org')->setTitle('org');
        $orgMember = new OrganizationMember();
        $orgMember->setUser($this->user)->setOrganization($org)->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->em->persist($org);
        $this->em->persist($orgMember);
        $this->em->flush();
        $this->em->refresh($this->user);

        $this->client->submit($form);

        $this->assertCount(1, $subscriberMock->events);
        $this->assertEquals(CancellationEvent::CANCELLATION_FAILURE, $subscriberMock->events[0]['type']);
        $this->assertEquals('Name', (string)$subscriberMock->events[0]['event']->getUser());
    }

}