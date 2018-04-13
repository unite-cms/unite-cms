<?php

namespace src\UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainInvitation;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class AcceptInvitationTest extends DatabaseAwareTestCase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller access check domain",
    "identifier": "access_check", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1"
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1"
      }
    ]
  }';

    /**
     * @var User[]
     */
    private $users;

    private $userPassword = 'XXXXXXXXX';

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test password reset')->setIdentifier('password_reset');

        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->users['domain_editor'] = new User();
        $this->users['domain_editor']->setEmail('domain_editor@example.com')->setFirstname(
            'Domain Editor'
        )->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword(
            $this->container->get('security.password_encoder')->encodePassword(
                $this->users['domain_editor'],
                $this->userPassword
            )
        );
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setRoles([Domain::ROLE_EDITOR])->setDomain($this->domain);
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);
        $this->users['domain_editor']->addDomain($domainEditorDomainMember);

        $this->users['domain_editor2'] = new User();
        $this->users['domain_editor2']->setEmail('domain_editor2@example.com')->setFirstname(
            'Domain Editor'
        )->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword(
            $this->container->get('security.password_encoder')->encodePassword(
                $this->users['domain_editor2'],
                $this->userPassword
            )
        );
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $this->users['domain_editor2']->addOrganization($domainEditorOrgMember);

        foreach ($this->users as $key => $user) {
            $this->em->persist($this->users[$key]);
        }

        $this->em->flush();

        foreach ($this->users as $key => $user) {
            $this->em->refresh($this->users[$key]);
        }
    }

    private function login(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    /**
     * An invitation for a user cannot be accepted if no invitation token is present.
     */
    public function testAcceptInvitationWithInvalidToken()
    {

        $crawler = $this->client->request('GET', '/profile/accept-invitation');
        $this->assertCount(1, $crawler->filter('div.uk-alert-danger'));
        $this->assertEquals($this->container->get('translator')->trans('profile.accept_invitation.token_missing'), trim($crawler->filter('div.uk-alert-danger')->text()));

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token=XXX');
        $this->assertCount(1, $crawler->filter('div.uk-alert-danger'));
        $this->assertEquals($this->container->get('translator')->trans('profile.accept_invitation.token_not_found'), trim($crawler->filter('div.uk-alert-danger')->text()));
    }

    /**
     * An invitation for a new user cannot be accepted if another user is logged in.
     */
    public function testAcceptInvitationForLoggedOutNewUser()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            'another_email@example.com'
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor']);

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals($this->container->get('translator')->trans('profile.accept_invitation.wrong_user'), trim($crawler->filter('div.uk-alert-warning')->text()));
    }

    /**
     * An invitation for a new user can be accepted if no user is logged in.
     */
    public function testAcceptInvitationForLoggedInNewUser()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            'another_email@example.com'
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Make sure, that a user with this email address does not exist but the invitation does.
        $this->assertNull(
            $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()])
        );
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId())
        );

        // Try to create a new, invalid user (full validation is tested somewhere else)
        $form['registration[password][first]'] = "pw1";
        $form['registration[password][second]'] = "pw2";
        $crawler = $this->client->submit($form);

        // Should show the form with form errors.
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertGreaterThan(0, $crawler->filter('form div.uk-alert-danger')->count());

        // Try to create a new, valid user
        $form['registration[password][first]'] = "password1";
        $form['registration[password][second]'] = "password1";
        $form['registration[firstname]'] = "First";
        $form['registration[lastname]'] = "Last";
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // User should be created.
        $user = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()]);
        $this->assertNotNull($user);

        // Check all user fields that should be filled upon registration.
        $this->assertEquals($invitation->getEmail(), $user->getEmail());
        $this->assertCount(1, $user->getDomains());
        $this->assertEquals(
            $invitation->getDomain()->getIdentifier(),
            $user->getDomains()->first()->getDomain()->getIdentifier()
        );
        $this->assertEquals($invitation->getRoles(), $user->getDomains()->first()->getRoles());
        $this->assertEquals('First', $user->getFirstname());
        $this->assertEquals('Last', $user->getLastname());
        $this->assertTrue($this->container->get('security.password_encoder')->isPasswordValid($user, 'password1'));

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a known user cannot be accepted if another user is logged in.
     */
    public function testAcceptInvitationForKnownLoggedOutUser()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            $this->users['domain_editor']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals($this->container->get('translator')->trans('profile.accept_invitation.wrong_user'), trim($crawler->filter('div.uk-alert-warning')->text()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is not
     * already member of the same domain.
     */
    public function testAcceptInvitationForMember()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            $this->users['domain_editor']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor']);

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals($this->container->get('translator')->trans('profile.accept_invitation.already_member'), trim($crawler->filter('div.uk-alert-warning')->text()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is logged in.
     */
    public function testAcceptInvitationForKnownLoggedInUser()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            $this->users['domain_editor2']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));

        // Make sure, that the invitation exists.
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId())
        );

        // Try to submit the form with operation accept
        $this->assertCount(0, $this->users['domain_editor2']->getDomains());
        $form = $crawler->selectButton($this->container->get('translator')->trans('invitation.accept'))->form();
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // Refresh user
        $existingUser = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor2']->getEmail()]
        );

        // Check all user fields that should be filled upon accept.
        $this->assertNotNull($existingUser);
        $this->assertCount(1, $existingUser->getDomains());
        $this->assertEquals(
            $invitation->getDomain()->getIdentifier(),
            $existingUser->getDomains()->first()->getDomain()->getIdentifier()
        );
        $this->assertEquals($invitation->getRoles(), $existingUser->getDomains()->first()->getRoles());

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is logged in.
     */
    public function testRejectInvitationForKnownLoggedInUser()
    {

        $invitation = new DomainInvitation();
        $invitation->setRoles(['ROLE_EDITOR'])->setDomain($this->domain)->setEmail(
            $this->users['domain_editor2']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', '/profile/accept-invitation?token='.$invitation->getToken());
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));

        // Make sure, that the invitation exists.
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId())
        );

        // Try to submit the form with operation reject
        $this->assertCount(0, $this->users['domain_editor2']->getDomains());
        $form = $crawler->selectButton($this->container->get('translator')->trans('invitation.reject'))->form();
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // Refresh user
        $existingUser = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor2']->getEmail()]
        );

        // Check that the user still is not member of the domain.
        $this->assertCount(0, $existingUser->getDomains());

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:DomainInvitation')->find($invitation->getId()));
    }
}
