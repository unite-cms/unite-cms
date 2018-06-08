<?php

namespace src\UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Invitation;
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
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test password reset')->setIdentifier('password_reset');

        $org2 = new Organization();
        $org2->setTitle('Org2')->setIdentifier('org2');

        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($org2);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($org2);
        $this->em->refresh($this->domain);

        $this->users['domain_editor'] = new User();
        $this->users['domain_editor']
            ->setEmail('domain_editor@example.com')
            ->setName('Domain Editor')
            ->setRoles([User::ROLE_USER])
            ->setPassword(
                static::$container->get('security.password_encoder')->encodePassword(
                    $this->users['domain_editor'],
                    $this->userPassword
                )
            );
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->first());
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);
        $this->users['domain_editor']->addDomain($domainEditorDomainMember);

        $this->users['domain_editor2'] = new User();
        $this->users['domain_editor2']
            ->setEmail('domain_editor2@example.com')
            ->setName('Domain Editor 2')
            ->setRoles([User::ROLE_USER])
            ->setPassword(
                static::$container->get('security.password_encoder')->encodePassword(
                    $this->users['domain_editor2'],
                    $this->userPassword
                )
            );
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($org2);
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

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation'));
        $this->assertCount(1, $crawler->filter('div.uk-alert-danger'));
        $this->assertEquals(static::$container->get('translator')->trans('profile.accept_invitation.token_missing'), trim($crawler->filter('div.uk-alert-danger')->text()));

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => 'XXX']));
        $this->assertCount(1, $crawler->filter('div.uk-alert-danger'));
        $this->assertEquals(static::$container->get('translator')->trans('profile.accept_invitation.token_not_found'), trim($crawler->filter('div.uk-alert-danger')->text()));
    }

    /**
     * An invitation for a new user cannot be accepted if another user is logged in.
     */
    public function testAcceptInvitationForLoggedOutNewUser()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            'another_email@example.com'
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals(static::$container->get('translator')->trans('profile.accept_invitation.wrong_user'), trim($crawler->filter('div.uk-alert-warning')->text()));
    }

    /**
     * An invitation for a new user can be accepted if no user is logged in.
     */
    public function testAcceptInvitationForLoggedInNewUser()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            'another_email@example.com'
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Make sure, that a user with this email address does not exist but the invitation does.
        $this->assertNull(
            $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()])
        );
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId())
        );

        // Try to create a new, invalid user (full validation is tested somewhere else)
        $form['invitation_registration[password][first]'] = "pw1";
        $form['invitation_registration[password][second]'] = "pw2";
        $crawler = $this->client->submit($form);

        // Should show the form with form errors.
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertGreaterThan(0, $crawler->filter('form div.uk-alert-danger')->count());

        // Try to create a new, valid user
        $form['invitation_registration[password][first]'] = "password1";
        $form['invitation_registration[password][second]'] = "password1";
        $form['invitation_registration[name]'] = "This is my name";
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // User should be created.
        $user = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()]);
        $this->assertNotNull($user);

        // Check all user fields that should be filled upon registration.
        $this->assertEquals($invitation->getEmail(), $user->getEmail());
        $this->assertCount(1, $user->getDomains());
        $this->assertEquals($invitation->getOrganization()->getIdentifier(), $user->getOrganizations()->first()->getOrganization()->getIdentifier());
        $this->assertEquals(
            $invitation->getDomainMemberType()->getDomain()->getIdentifier(),
            $user->getDomains()->first()->getDomain()->getIdentifier()
        );
        $this->assertEquals('This is my name', $user->getName());
        $this->assertTrue(static::$container->get('security.password_encoder')->isPasswordValid($user, 'password1'));

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a new user can be accepted if no user is logged in.
     */
    public function testAcceptInvitationWithoutDomainForLoggedInNewUser()
    {

        $invitation = new Invitation();
        $invitation
            ->setOrganization($this->organization)
            ->setEmail('another_email@example.com')
            ->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))
            ->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Make sure, that a user with this email address does not exist but the invitation does.
        $this->assertNull(
            $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()])
        );
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId())
        );

        // Try to create a new, invalid user (full validation is tested somewhere else)
        $form['invitation_registration[password][first]'] = "pw1";
        $form['invitation_registration[password][second]'] = "pw2";
        $crawler = $this->client->submit($form);

        // Should show the form with form errors.
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertGreaterThan(0, $crawler->filter('form div.uk-alert-danger')->count());

        // Try to create a new, valid user
        $form['invitation_registration[password][first]'] = "password1";
        $form['invitation_registration[password][second]'] = "password1";
        $form['invitation_registration[name]'] = "This is my name";
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // User should be created.
        $user = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(['email' => $invitation->getEmail()]);
        $this->assertNotNull($user);

        // Check all user fields that should be filled upon registration.
        $this->assertEquals($invitation->getEmail(), $user->getEmail());
        $this->assertCount(0, $user->getDomains());
        $this->assertEquals($invitation->getOrganization()->getIdentifier(), $user->getOrganizations()->first()->getOrganization()->getIdentifier());
        $this->assertEquals('This is my name', $user->getName());
        $this->assertTrue(static::$container->get('security.password_encoder')->isPasswordValid($user, 'password1'));

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a known user cannot be accepted if another user is logged in.
     */
    public function testAcceptInvitationForKnownLoggedOutUser()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            $this->users['domain_editor']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals(static::$container->get('translator')->trans('profile.accept_invitation.wrong_user'), trim($crawler->filter('div.uk-alert-warning')->text()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is not
     * already member of the same domain and the same type.
     */
    public function testAcceptInvitationForMember()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            $this->users['domain_editor']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(1, $crawler->filter('div.uk-alert-warning'));
        $this->assertEquals(static::$container->get('translator')->trans('profile.accept_invitation.already_member', ['%organization%' => (string)$this->organization]), trim($crawler->filter('div.uk-alert-warning p')->html()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is logged in.
     */
    public function testAcceptInvitationForKnownLoggedInUser()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            $this->users['domain_editor2']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));

        // Make sure, that the invitation exists.
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId())
        );

        // Try to submit the form with operation accept
        $this->assertCount(0, $this->users['domain_editor2']->getDomains());
        $form = $crawler->selectButton(static::$container->get('translator')->trans('invitation.accept'))->form();
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
            $invitation->getDomainMemberType()->getDomain()->getIdentifier(),
            $existingUser->getDomains()->first()->getDomain()->getIdentifier()
        );
        $this->assertEquals(Organization::ROLE_USER, $existingUser->getOrganizations()->first()->getSingleRole());

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is logged in.
     */
    public function testAcceptInvitationWithoutDomainForKnownLoggedInUser()
    {

        $invitation = new Invitation();
        $invitation
            ->setOrganization($this->organization)
            ->setEmail($this->users['domain_editor2']->getEmail())
            ->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))
            ->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));

        // Make sure, that the invitation exists.
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId())
        );

        // Before submitting, the user is member of one org.
        $this->assertCount(1, $this->users['domain_editor2']->getOrganizations());

        // Try to submit the form with operation accept
        $this->assertCount(0, $this->users['domain_editor2']->getDomains());
        $form = $crawler->selectButton(static::$container->get('translator')->trans('invitation.accept'))->form();
        $crawler = $this->client->submit($form);

        // Should not show a form
        $this->assertCount(0, $crawler->filter('form'));

        // Refresh user
        $existingUser = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor2']->getEmail()]
        );

        // Check all user fields that should be filled upon accept.
        $this->assertNotNull($existingUser);
        $this->assertCount(2, $existingUser->getOrganizations());
        $this->assertCount(0, $existingUser->getDomains());
        $this->assertEquals($invitation->getOrganization()->getIdentifier(), $existingUser->getOrganizations()->last()->getOrganization()->getIdentifier());
        $this->assertEquals(Organization::ROLE_USER, $existingUser->getOrganizations()->last()->getSingleRole());

        // Also make sure, that the invitation got deleted.
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId()));
    }

    /**
     * An invitation for a known user can only be accepted if the user is logged in.
     */
    public function testRejectInvitationForKnownLoggedInUser()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            $this->users['domain_editor2']->getEmail()
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $this->login($this->users['domain_editor2']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));
        $this->assertCount(0, $crawler->filter('div.uk-alert-danger'));
        $this->assertCount(1, $crawler->filter('form'));

        // Make sure, that the invitation exists.
        $this->assertNotNull(
            $this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId())
        );

        // Try to submit the form with operation reject
        $this->assertCount(0, $this->users['domain_editor2']->getDomains());
        $form = $crawler->selectButton(static::$container->get('translator')->trans('invitation.reject'))->form();
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
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Invitation')->find($invitation->getId()));
    }

    /**
     * After creating a new user account during invitation, the user should get logged in automatically.
     */
    public function testAcceptInvitationAutoLogin()
    {

        $invitation = new Invitation();
        $invitation->setOrganization($this->organization)->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setEmail(
            'test@example.com'
        )->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['invitation_registration[name]'] = 'New User';
        $form['invitation_registration[password][first]'] = 'password';
        $form['invitation_registration[password][second]'] = 'password';
        $this->client->submit($form);

        // There should be a user token in the client session.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_view', [
            'organization' => $invitation->getDomainMemberType()->getDomain()->getOrganization()->getIdentifier(),
            'domain' => $invitation->getDomainMemberType()->getDomain()->getIdentifier(),
        ])));
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotNull($token);
        $this->assertEquals('New User', $token->getUser()->getName());
        $this->assertEquals($invitation->getEmail(), $token->getUser()->getEmail());
    }

    /**
     * After creating a new user account during invitation, the user should get logged in automatically.
     */
    public function testAcceptInvitationWithoutDomainAutoLogin()
    {

        $invitation = new Invitation();
        $invitation
            ->setOrganization($this->organization)
            ->setEmail('test@example.com')
            ->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='))
            ->setRequestedAt(new \DateTime());
        $this->em->persist($invitation);
        $this->em->flush();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]));

        $form = $crawler->filter('form');
        $this->assertCount(1, $form);
        $form = $form->form();

        $form['invitation_registration[name]'] = 'New User';
        $form['invitation_registration[password][first]'] = 'password';
        $form['invitation_registration[password][second]'] = 'password';
        $this->client->submit($form);

        // There should be a user token in the client session.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => $invitation->getOrganization()->getIdentifier(),
        ])));
        $token = $this->client->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotNull($token);
        $this->assertEquals('New User', $token->getUser()->getName());
        $this->assertEquals($invitation->getEmail(), $token->getUser()->getEmail());
    }
}
