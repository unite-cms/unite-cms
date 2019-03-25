<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.01.18
 * Time: 12:57
 */

namespace UniteCMS\CoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

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
     * @var User $editor
     */
    private $editor;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var DomainConfigManager $manager
     */
    private $manager;

    public function setUp()
    {
        parent::setUp();
        $this->manager = static::$container->get('unite.cms.domain_config_manager');
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

        $this->editor = new User();
        $this->editor->setEmail('editor@example.com')->setName('Domain Editor')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $this->editor->addOrganization($domainEditorOrgMember);

        $this->em->persist($this->admin);
        $this->em->persist($this->editor);
        $this->em->flush();
        $this->em->refresh($this->admin);
        $this->em->refresh($this->editor);
    }

    private function login(User $user) {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testCRUDActionsAsAdmin() {

        $this->login($this->admin);

        // List all domains.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check that there is a add domain button.
        $addButton = $crawler->filter('a:contains("' . static::$container->get('translator')->trans('organization.menu.domains.add') . '")');
        $this->assertGreaterThanOrEqual(1, $addButton->count());
        $crawler = $this->client->click($addButton->first()->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid JSON
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['domain'] = 'foo baa';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("Could not parse domain definition JSON.")'));

        // Submit invalid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['domain'] = '{ "foo": "baa" }';

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("title: '.static::$container->get('translator')->trans('not_blank', [], 'validators').'")'));
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("identifier: '.static::$container->get('translator')->trans('not_blank', [], 'validators').'")'));

        // Because we don't reboot the kernel on each request, clear all previous created but not persisted entities.
        $this->em->clear();

        // Submit valid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['domain'] = '{ "title": "Domain 1", "identifier": "d1_domain_controller_test" }';
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->client->enableReboot();

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_view', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => IdentifierNormalizer::denormalize('d1_domain_controller_test'),
        ], Router::ABSOLUTE_URL)));

        $crawler = $this->client->followRedirect();

        // Assert domain creation.
        $domain = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findOneBy([
            'organization' => $this->organization,
            'identifier' => 'd1_domain_controller_test',
        ]);

        // Assert domain config file.
        $this->assertNotNull($domain);
        $this->assertTrue(static::$container->get('unite.cms.domain_config_manager')->configExists($domain));
        static::$container->get('unite.cms.domain_config_manager')->loadConfig($domain);

        $domainSerialized = static::$container->get('unite.cms.domain_config_manager')->serialize($domain);
        $this->assertJsonStringEqualsJsonString($values['form']['domain'], $domain->getConfig());
        $this->assertJsonStringEqualsJsonString($domainSerialized, static::$container->get('unite.cms.domain_config_manager')->serialize(
            static::$container->get('unite.cms.domain_config_manager')->parse($domain->getConfig()))
        );

        $this->client->disableReboot();

        $updateButton = $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.update') .'")');
        $this->assertGreaterThanOrEqual(1, $updateButton->count());
        $crawler = $this->client->click($updateButton->first()->link());

        // Assert update form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid JSON
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['domain'] = 'foo baa';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("Could not parse domain definition JSON.")'));

        // Submit invalid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['domain'] = '{ "foo": "baa" }';
        $values['form']['submit'] = '';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertFalse($this->client->getResponse()->isRedirect());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("title: '.static::$container->get('translator')->trans('not_blank', [], 'validators').'")'));
        $this->assertCount(1, $crawler->filter('.uk-alert-danger p:contains("identifier: '.static::$container->get('translator')->trans('not_blank', [], 'validators').'")'));

        // Submit valid Domain definition.
        $form = $crawler->filter('form');
        $form = $form->form();
        $form->disableValidation();
        $values['form']['domain'] = '{
            "title": "@replaced_by_variable",
            "identifier": "d1_domain_controller_test",
            "variables": {
                "@replaced_by_variable": "Domain 1"
            },
            "permissions": {
                "view domain": "true",
                "update domain": "member.type == \"user\""
            }
        }';
        $values['form']['submit'] = '';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // assert confirmation page.
        $this->assertCount(1, $crawler->filter('.unite-domain-change-visualization'));
        $this->assertCount(1, $crawler->filter('button[name="form[back]"]'));

        // click on back button.
        $values['form']['back'] = '';
        unset($values['form']['submit']);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // we should see the edit page again.
        $this->assertCount(0, $crawler->filter('.unite-domain-change-visualization'));
        $this->assertCount(1, $crawler->filter('button[name="form[submit]"]'));

        // submit.
        $values['form']['submit'] = '';
        unset($values['form']['back']);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertCount(1, $crawler->filter('.unite-domain-change-visualization'));
        $this->assertCount(1, $crawler->filter('button[name="form[confirm]"]'));

        // click on confirmation button.
        $values['form']['confirm'] = '';
        unset($values['form']['submit']);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_view', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => IdentifierNormalizer::denormalize('d1_domain_controller_test'),
        ], Router::ABSOLUTE_URL)));

        $crawler = $this->client->followRedirect();

        // Assert domain update.
        $this->em->refresh($domain);
        $this->assertNotNull($domain);
        $this->assertEquals([
            'view domain' => 'true',
            'update domain' => 'member.type == "user"',
        ], $domain->getPermissions());

        // Assert domain config file.
        $this->assertTrue(static::$container->get('unite.cms.domain_config_manager')->configExists($domain));
        $domainSerialized = static::$container->get('unite.cms.domain_config_manager')->serialize($domain);
        static::$container->get('unite.cms.domain_config_manager')->loadConfig($domain);


        $this->assertJsonStringEqualsJsonString($domainSerialized, static::$container->get('unite.cms.domain_config_manager')->serialize($domain));

        // Make sure, that the serialized domain config is equal to the initial saved one.
        $this->assertJsonStringEqualsJsonString($values['form']['domain'], $domain->getConfig());

        $this->assertEquals('Domain 1', $domain->getTitle());

        // Click on domain delete.
        $deleteButton = $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.trash') .'")');
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
        $domainUser->setEmail('example@example.com')->setName('Example')->setPassword('XXX');
        $domainUserOrg = new OrganizationMember();
        $domainUserOrg->setUser($domainUser)->setOrganization($domain->getOrganization());
        $domainUserDomain = new DomainMember();
        $domainUserDomain->setAccessor($domainUser)->setDomain($domain);
        $this->em->persist($domainUser);
        $this->em->persist($domainUserOrg);
        $this->em->persist($domainUserDomain);
        $this->em->flush();

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
        ], Router::ABSOLUTE_URL)));
        $this->client->followRedirect();

        // Assert domain was deleted.
        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll());

        // Assert domain config file was deleted.
        $this->assertFalse(static::$container->get('unite.cms.domain_config_manager')->configExists($domain));
    }

    public function testCRUDActionsAsEditor() {

        $this->login($this->editor);

        // Create test domain.
        $domain = new Domain();
        $domain
            ->setIdentifier('test')
            ->setTitle('Test')
            ->setOrganization($this->organization);

        $domain->addPermission(DomainVoter::UPDATE, 'member.accessor.name == "Domain Admin"');

        $this->em->persist($domain);
        $this->em->flush($domain);
        $this->em->refresh($domain);

        // List all domains.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // org editors are not automatically domain editors.
        $this->assertCount(0, $crawler->filter('a .uk-margin-small-right[data-feather="globe"]'));

        // Org editors are not allowed to add new domains.
        $this->assertCount(0, $crawler->filter('a:contains("' . static::$container->get('translator')->trans('organization.menu.domains.add') . '")'));

        // Add this editor to the domain.
        $domainMember = new DomainMember();
        $domainMember->setDomain($domain)->setAccessor($this->editor);
        $this->em->persist($domainMember);
        $this->em->flush($domainMember);
        $this->em->refresh($domainMember);

        // Because we don't reboot the kernel on each request, clear all previous created but not persisted entities.
        $this->em->clear();

        $crawler = $this->client->reload();

        // editor now sees domain.
        $domainIcon = $crawler->filter('a .uk-margin-small-right[data-feather="globe"]');
        $this->assertCount(1, $domainIcon);
        $crawler = $this->client->click($domainIcon->parents()->first()->link());

        // org editors are not allowed to edit domains.
        $this->assertCount(0, $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.update') .'")'));
        $this->assertCount(0, $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.trash') .'")'));
        $this->assertCount(0, $crawler->filter('li:contains("' . static::$container->get('translator')->trans('domain.menu.domain_member_types.headline') .'")'));

        // Make this domain member an domain administrator (according to update permission expression).
        $this->editor = $this->em->getRepository('UniteCMSCoreBundle:User')->findOneBy([
            'email' => $this->editor->getEmail(),
        ]);
        $this->editor->setName('Domain Admin');
        $this->em->flush($this->editor);
        $this->em->refresh($this->editor);

        $this->client->enableReboot();
        $crawler = $this->client->reload();
        $this->client->disableReboot();

        // org editors, that are domain admins are allowed to edit domain.
        $this->assertCount(1, $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.update') .'")'));
        $this->assertCount(1, $crawler->filter('li:contains("' . static::$container->get('translator')->trans('domain.menu.domain_member_types.headline') .'")'));

        // but are not allowed to delete the domain.
        $this->assertCount(0, $crawler->filter('a:contains("' . static::$container->get('translator')->trans('domain.menu.manage.trash') .'")'));

        // delete file for next next
        static::$container->get('unite.cms.domain_config_manager')->removeConfig($domain);
    }

    public function testCreateFromConfigAndUpdateFromConfig() {

        // Create a test config for the current domain.
        $domain = new Domain();
        $domain->setOrganization($this->organization)->setIdentifier('test_create_domain_from_config')->setTitle('Custom title');
        static::$container->get('unite.cms.domain_config_manager')->updateConfig($domain, true);
        $this->organization->getDomains()->removeElement($domain);
        $this->assertFileExists(static::$container->get('unite.cms.domain_config_manager')->getDomainConfigPath($domain));
        unset($domain);

        $this->login($this->admin);

        // List all domains.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_domain_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check that there is a import domain button
        $importSection = $crawler->filter('li:contains("' . static::$container->get('translator')->trans('organization.menu.new_domains_from_config.header') . '")');
        $this->assertCount(1, $importSection);
        $crawler = $this->client->click($importSection->nextAll()->first()->children()->first()->link());

        // Check, that config is pre-filled with the domain identifier.
        $form = $crawler->filter('form');
        $editorValue = json_decode($form->filter('unite-cms-core-domaineditor')->attr('value'));
        $this->assertEquals('test_create_domain_from_config', $editorValue->identifier);
        $this->assertEquals('Custom title', $editorValue->title);
        $this->em->clear();

        // Submit create from should create new domain.
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['domain'] = json_encode($editorValue);
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->client->enableReboot();

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_view', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => IdentifierNormalizer::denormalize('test_create_domain_from_config'),
        ], Router::ABSOLUTE_URL)));

        $crawler = $this->client->followRedirect();

        // Assert domain creation.
        $domain = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findOneBy([
            'organization' => $this->organization,
            'identifier' => 'test_create_domain_from_config',
        ]);
        $this->assertNotNull($domain);
        $this->assertEquals('test_create_domain_from_config', $domain->getIdentifier());

        // If the config file was updated, we should see a warning on the domain update page
        file_put_contents(static::$container->get('unite.cms.domain_config_manager')->getDomainConfigPath($domain), '{
            "title": "Updated",
            "identifier": "test_create_domain_from_config",
            "content_types": [{ "title": "Foo", "identifier": "foo" }]
        }');

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_domain_update', [$domain], Router::ABSOLUTE_URL));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertNotNull($crawler->filter('.uk-alert-warning:contains("The filesystem config of this domain is different from the current config. You can use the diff tool to update the config.")'));

        // Merge the diff value into the value and submit.
        $form = $crawler->filter('form');
        $editorValue = json_decode($form->filter('unite-cms-core-domaineditor')->attr('value'));
        $this->assertEquals('test_create_domain_from_config', $editorValue->identifier);
        $this->assertEquals('Updated', $editorValue->title);
        $this->em->clear();

        // Submit create from should create new domain.
        $form = $form->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['form']['domain'] = json_encode($editorValue);
        $values['form']['submit'] = '';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // assert confirmation page.
        $this->assertCount(1, $crawler->filter('.unite-domain-change-visualization'));

        // click on confirmation button.
        $values['form']['confirm'] = '';
        unset($values['form']['submit']);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_domain_view', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => IdentifierNormalizer::denormalize('test_create_domain_from_config'),
        ], Router::ABSOLUTE_URL)));

        $crawler = $this->client->followRedirect();

        // Assert domain creation.
        $domain = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findOneBy([
            'organization' => $this->organization,
            'identifier' => 'test_create_domain_from_config',
        ]);
        $this->assertNotNull($domain);
        $this->assertEquals('Updated', $domain->getTitle());

    }
}
