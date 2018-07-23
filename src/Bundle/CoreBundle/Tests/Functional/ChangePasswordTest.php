<?php

namespace src\UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ChangePasswordTest extends DatabaseAwareTestCase
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

    private $userPassword = 'XXXXXXXXX';

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
        $this->client->disableReboot();

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
            ->setPassword(
                static::$container->get('security.password_encoder')->encodePassword(
                    $this->users['domain_editor'],
                    $this->userPassword
                )
            );
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

    private function login(User $user)
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testChangePasswordAction()
    {

        $this->login($this->users['domain_editor']);
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_profile_update', [], Router::ABSOLUTE_URL));

        // Make sure, that there are two forms. One for profile update, on for password reset.
        $this->assertCount(1, $crawler->filter('form[name="change_password"]'));
        $form = $crawler->filter('form[name="change_password"]')->form();

        // Try to update password with invalid password.
        $new_RandPassword = 't_short';
        $form['change_password[newPassword][first]'] = $new_RandPassword;
        $form['change_password[newPassword][second]'] = $new_RandPassword;
        $crawler = $this->client->submit($form);

        // Should show the form with an form error.
        $this->assertCount(1, $crawler->filter('form[name="change_password"]'));
        $this->assertCount(2, $crawler->filter('form[name="change_password"] div.uk-alert-danger'));

        // Try to update the password with valid password but not current password.
        $new_RandPassword = 'valid_password'.time();
        $form['change_password[newPassword][first]'] = $new_RandPassword;
        $form['change_password[newPassword][second]'] = $new_RandPassword;
        $crawler = $this->client->submit($form);

        // Should show the form with an form error.
        $this->assertCount(1, $crawler->filter('form[name="change_password"]'));
        $this->assertCount(1, $crawler->filter('form[name="change_password"] div.uk-alert-danger'));

        // Try to update the password with valid password.
        $new_RandPassword = 'valid_password'.time();
        $form['change_password[newPassword][first]'] = $new_RandPassword;
        $form['change_password[newPassword][second]'] = $new_RandPassword;
        $form['change_password[currentPassword]'] = $this->userPassword;
        $crawler = $this->client->submit($form);

        // Should show the form with no form error.
        $this->assertCount(0, $crawler->filter('form'));

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
