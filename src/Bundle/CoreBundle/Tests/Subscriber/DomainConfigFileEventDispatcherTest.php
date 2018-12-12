<?php

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UniteCMS\CoreBundle\Event\DomainConfigFileEvent;

class DomainConfigEventDispatcherTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $admin
     */
    private $admin;

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
        $this->client->disableReboot();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1_org1');
        $this->em->persist($this->organization);
        $this->em->flush();
        $this->em->refresh($this->organization);

        $this->admin = new User();
        $this->admin->setEmail('admin@example.com')->setName('Domain Admin')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainAdminOrgMember = new OrganizationMember();
        $domainAdminOrgMember->setRoles([Organization::ROLE_ADMINISTRATOR])->setOrganization($this->organization);
        $this->admin->addOrganization($domainAdminOrgMember);

        $this->em->persist($this->admin);
        $this->em->flush();
        $this->em->refresh($this->admin);

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

            public function onCreate(DomainConfigFileEvent $event)
            {
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_CREATE, 'event' => $event];
            }

            public function onUpdate(DomainConfigFileEvent $event)
            {
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE, 'event' => $event];
            }

            public function onDelete(DomainConfigFileEvent $event)
            {
                $this->events[] = ['type' => DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE, 'event' => $event];
            }
        };

        $this->client->getContainer()->get('event_dispatcher')->addSubscriber($this->subscriberMock);
        $this->client->disableReboot();
    }

    private function login(User $user) {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testDomainConfigTriggerEvents()
    {
        $this->login($this->admin);

        // Create test domain.
        $domain = new Domain();
        $domain
            ->setIdentifier('test')
            ->setTitle('Test')
            ->setOrganization($this->organization);
    
        $this->em->persist($domain);
        $this->em->flush($domain);
        $this->em->refresh($domain);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_domain_update', [$domain], Router::ABSOLUTE_URL));

        $form = $crawler->filter('form');
        #$this->assertCount(1, $form);
        #$editorValue = json_decode($form->filter('unite-cms-core-domaineditor')->attr('value'));
        #$form = $form->form();
        #$form['domain'] = json_encode($editorValue);

        #$this->client->submit($form);

        #$this->assertCount(1, $this->subscriberMock->events);
        #$this->assertEquals(CancellationEvent::CANCELLATION_FAILURE, $subscriberMock->events[0]['type']);
        #$this->assertEquals('Name', (string)$subscriberMock->events[0]['event']->getUser());

    }
}
