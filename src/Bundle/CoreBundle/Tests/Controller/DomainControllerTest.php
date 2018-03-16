<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.01.18
 * Time: 12:57
 */

namespace UnitedCMS\CoreBundle\Tests\Controller;


use Symfony\Component\HttpKernel\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DomainControllerTest extends DatabaseAwareTestCase
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

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1');
        $this->em->persist($this->organization);
        $this->em->flush();
        $this->em->refresh($this->organization);

        $this->admin = new User();
        $this->admin->setEmail('editor@example.com')->setFirstname('Domain Admin')->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_ADMINISTRATOR])->setOrganization($this->organization);
        $this->admin->addOrganization($domainEditorOrgMember);

        $this->em->persist($this->admin);
        $this->em->flush();
        $this->em->refresh($this->admin);

        $token = new UsernamePasswordToken($this->admin, null, 'main', $this->admin->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testCRUDActions() {


        // List all domains.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_domain_index', [
            'organization' => $this->organization->getIdentifier(),
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check that there is a add domain button.
        $addButton = $crawler->filter('a:contains("' . $this->container->get('translator')->trans('organization.menu.domains.add') . '")');
        $this->assertGreaterThanOrEqual(1, $addButton->count());
        $crawler = $this->client->click($addButton->first()->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid JSON
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['definition'] = 'foo baa';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("Could not parse domain definition JSON.")'));

        // Submit invalid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['definition'] = '{ "foo": "baa" }';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("title: validation.not_blank")'));
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("identifier: validation.not_blank")'));

        // Submit valid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['definition'] = '{ "title": "Domain 1", "identifier": "d1" }';
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_domain_view', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => 'd1',
        ])));
        $crawler = $this->client->followRedirect();
        $updateButton = $crawler->filter('a:contains("' . $this->container->get('translator')->trans('domain.menu.manage.update') .'")');
        $this->assertGreaterThanOrEqual(1, $updateButton->count());
        $crawler = $this->client->click($updateButton->first()->link());

        // Assert update form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid JSON
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['definition'] = 'foo baa';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("Could not parse domain definition JSON.")'));

        // Submit invalid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['definition'] = '{ "foo": "baa" }';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("title: validation.not_blank")'));
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("identifier: validation.not_blank")'));

        // Submit valid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['definition'] = '{ "title": "Domain 1", "identifier": "d1", "roles": [
            "ROLE_PUBLIC",
            "ROLE_EDITOR",
            "ROLE_ADMINISTRATOR",
            "ROLE_FOO"
        ] }';
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_domain_view', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => 'd1',
        ])));
        $crawler = $this->client->followRedirect();

        // make sure, that the domain was updated.
        $domain = $this->em->getRepository('UnitedCMSCoreBundle:Domain')->findAll()[0];
        $this->assertEquals([
            'ROLE_PUBLIC',
            'ROLE_EDITOR',
            'ROLE_ADMINISTRATOR',
            'ROLE_FOO',
        ], $domain->getRoles());

        // Click on domain delete.
        $deleteButton = $crawler->filter('a:contains("' . $this->container->get('translator')->trans('domain.menu.manage.trash') .'")');
        $this->assertGreaterThanOrEqual(1, $deleteButton->count());
        $crawler = $this->client->click($deleteButton->first()->link());

        // Assert delete form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form
        $form = $form->form();
        $form['form[_token]'] = 'invalid';
        $crawler = $this->client->submit($form);

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("The CSRF token is invalid. Please try to resubmit the form.")'));

        // Test delete domain not allowed.
        $domainUser = new User();
        $domainUser->setEmail('example@example.com')->setFirstname('Example')->setLastname('Example')->setPassword('XXX');
        $domainUserOrg = new OrganizationMember();
        $domainUserOrg->setUser($domainUser)->setOrganization($domain->getOrganization());
        $domainUserDomain = new DomainMember();
        $domainUserDomain->setUser($domainUser)->setDomain($domain);
        $this->em->persist($domainUser);
        $this->em->persist($domainUserOrg);
        $this->em->persist($domainUserDomain);
        $this->em->flush();

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $crawler = $this->client->submit($form);

        // Should not delete domain, since we have a domain user.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("Domain could not be deleted.")'));

        $this->em->remove($domainUserDomain);
        $this->em->flush();

        // Now deletion should work.
        $form = $crawler->filter('form');
        $form = $form->form();
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_domain_index', [
            'organization' => $this->organization->getIdentifier(),
        ])));
        $this->client->followRedirect();

        // Assert domain was deleted.
        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Domain')->findAll());
    }
}