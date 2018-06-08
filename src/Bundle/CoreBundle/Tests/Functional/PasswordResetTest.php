<?php

namespace src\UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class PasswordResetTest extends DatabaseAwareTestCase
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
     * @var User[]
     */
    private $users;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test password reset')->setIdentifier('password_reset');

        $this->em->persist($this->organization);
        $this->em->flush();
        $this->em->refresh($this->organization);

        $this->users['domain_editor'] = new User();
        $this->users['domain_editor']
            ->setEmail('domain_editor@example.com')
            ->setName('Domain Editor')
            ->setRoles([User::ROLE_USER])
            ->setPassword('XXX');

        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);

        foreach ($this->users as $key => $user) {
            $this->em->persist($this->users[$key]);
        }

        $this->em->flush();

        foreach ($this->users as $key => $user) {
            $this->em->refresh($this->users[$key]);
        }
    }

    public function testPasswordResetActionForUnknownEmail()
    {

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_resetpassword'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Try to reset unknown email address.
        $form['form[username]'] = 'unknwon_email_address';
        $crawler = $this->client->submit($form);

        // Should show the success message instead of the form
        $this->assertCount(0, $crawler->filter('form'));
    }

    public function testPasswordResetActionForKnownEmail()
    {

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_resetpassword'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Try to reset known email address.
        $form['form[username]'] = $this->users['domain_editor']->getEmail();
        $crawler = $this->client->submit($form);

        // Should show the success message instead of the form
        $this->assertCount(0, $crawler->filter('form'));

        // And user should have a reset token.
        $this->users['domain_editor'] = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor']->getEmail()]
        );
        $this->assertNotNull($this->users['domain_editor']->getResetToken());
        $this->assertNotNull($this->users['domain_editor']->getResetRequestedAt());
    }

    public function test2ndPasswordResetAction()
    {

        $resetToken = $this->users['domain_editor']->setResetToken(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        )->getResetToken();
        $resetRequestedAt = $this->users['domain_editor']->setResetRequestedAt(new \DateTime())->getResetRequestedAt();
        $this->em->flush($this->users['domain_editor']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_resetpassword'));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Try to reset known email address again.
        $form['form[username]'] = $this->users['domain_editor']->getEmail();
        $crawler = $this->client->submit($form);

        // Should show the form with an form error.
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('form div.uk-alert-danger'));

        // Reset token should not be updated.
        $this->users['domain_editor'] = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor']->getEmail()]
        );
        $this->assertEquals($resetToken, $this->users['domain_editor']->getResetToken());
        $this->assertEquals($resetRequestedAt, $this->users['domain_editor']->getResetRequestedAt());
    }

    public function testConfirmActionForUnknownResetToken()
    {
        $crawler = $this->client->request(
            'GET',
            '/profile/reset-password-confirm?token='.rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        );
        $this->assertCount(0, $crawler->filter('form'));
    }

    public function testConfirmActionForExpiredResetToken()
    {

        $resetToken = $this->users['domain_editor']->setResetToken(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        )->getResetToken();
        $this->users['domain_editor']->setResetRequestedAt(new \DateTime())->getResetRequestedAt()->sub(
            new \DateInterval('PT'.User::PASSWORD_RESET_TTL.'S')
        );
        $this->assertTrue($this->users['domain_editor']->isResetRequestExpired());
        $this->em->flush($this->users['domain_editor']);

        $crawler = $this->client->request('GET', '/profile/reset-password-confirm?token='.$resetToken);
        $this->assertCount(0, $crawler->filter('form'));
    }

    public function testConfirmActionForValidResetToken()
    {

        $resetToken = $this->users['domain_editor']->setResetToken(
            rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')
        )->getResetToken();
        $this->users['domain_editor']->setResetRequestedAt(new \DateTime());
        $this->em->flush($this->users['domain_editor']);

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_resetpasswordconfirm', ['token' => $resetToken]));
        $this->assertCount(1, $crawler->filter('form'));
        $form = $crawler->filter('form')->form();

        // Try to update password with invalid password.
        $new_RandPassword = 't_short';
        $form['change_password[newPassword][first]'] = $new_RandPassword;
        $form['change_password[newPassword][second]'] = $new_RandPassword;
        $crawler = $this->client->submit($form);

        // Should show the form with an form error.
        $this->assertCount(1, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('form div.uk-alert-danger'));

        // Try to update the password with valid password.
        $new_RandPassword = 'valid_password'.time();
        $form['change_password[newPassword][first]'] = $new_RandPassword;
        $form['change_password[newPassword][second]'] = $new_RandPassword;
        $crawler = $this->client->submit($form);

        // Should show the form with no form error and a success messages.
        $this->assertCount(0, $crawler->filter('form'));
        $this->assertCount(1, $crawler->filter('h3'));

        // Password should be updated.
        $this->users['domain_editor'] = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['email' => $this->users['domain_editor']->getEmail()]
        );
        $this->assertTrue(
            static::$container->get('security.password_encoder')->isPasswordValid(
                $this->users['domain_editor'],
                $new_RandPassword
            )
        );
    }
}
