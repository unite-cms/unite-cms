<?php

namespace UniteCMS\CoreBundle\Tests\Controller;

use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class SettingControllerTest extends DatabaseAwareTestCase {

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var User $editor
     */
    private $editor;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller access check domain",
    "identifier": "d1", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "choice", "settings": { "choices": ["a", "b"] } }
        ], 
        "views": [
            { "title": "All", "identifier": "all", "type": "table" },
            { "title": "Other", "identifier": "other", "type": "table" }
        ],
        "locales": ["de", "en"]
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "choice", "settings": { "choices": ["a", "b"] } },
            { "title": "Field 3", "identifier": "f3", "type": "reference", "settings": { "domain": "d1", "content_type": "ct1" } }
        ],
        "permissions": {
            "view setting": [
              "ROLE_EDITOR"
            ],
            "update setting": [
              "ROLE_EDITOR"
            ]
          },
        "locales": ["de", "en"]
      }
    ]
  }';

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1');
        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->editor = new User();
        $this->editor->setEmail('editor@example.com')->setName('Domain Editor')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setRoles([Domain::ROLE_EDITOR])->setDomain($this->domain);
        $this->editor->addOrganization($domainEditorOrgMember);
        $this->editor->addDomain($domainEditorDomainMember);

        $this->em->persist($this->editor);
        $this->em->flush();
        $this->em->refresh($this->editor);

        $token = new UsernamePasswordToken($this->editor, null, 'main', $this->editor->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testCRUDActions() {

        $url_list = $this->container->get('router')->generate('unitecms_core_setting_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
        ]);

        $crawler = $this->client->request('GET', $url_list);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data
        $form = $form->form();
        $form->disableValidation();
        $form['fieldable_form[f1]'] = 'Updated Field value 1';
        $form['fieldable_form[f2]'] = 'invalid';
        $form['fieldable_form[locale]'] = 'it';
        $crawler = $this->client->submit($form);

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_locale + .uk-alert-danger p:contains("This value is not valid.")'));
        $this->assertCount(1, $crawler->filter('#fieldable_form_f2 + .uk-alert-danger p:contains("This value is not valid.")'));

        $form = $crawler->filter('form');
        $form = $form->form();
        $form['fieldable_form[f1]'] = 'Updated Field value 1';
        $form['fieldable_form[f2]'] = 'b';
        $form['fieldable_form[locale]'] = 'en';

        $this->client->submit($form);

        // Assert update
        $setting = $this->em->getRepository('UniteCMSCoreBundle:Setting')->findOneBy([
            'settingType' => $this->domain->getSettingTypes()->first(),
            'locale' => 'en',
        ]);
        $this->assertEquals('Updated Field value 1', $setting->getData()['f1']);
        $this->assertEquals('b', $setting->getData()['f2']);
    }

    public function testSettingValidation() {

        // Create setting.
        $setting = new Setting();
        $setting->setSettingType($this->domain->getSettingTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($setting);
        $this->em->flush($setting);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll());

        // Test update content validation.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_setting_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data.
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['fieldable_form']['f3'] = [
            'content' => 'any'
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p:contains("validation.missing_definition")'));

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data.
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['fieldable_form']['f3'] = [
            'domain' => 'any',
            'content_type' => 'any',
            'content' => 'any'
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p:contains("validation.wrong_definition")'));
    }

    public function testTranslateActions() {

        // Create setting.
        $setting = new Setting();
        $setting->setSettingType($this->domain->getSettingTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($setting);
        $this->em->flush($setting);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll());

        // Try to access translations page with invalid content id.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_setting_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
            'setting' => $doctrineUUIDGenerator->generate($this->em, $setting),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to access translations page with valid setting id.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_setting_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
            'setting' => $setting->getId(),
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Create english translation.
        $crawler = $this->client->click($crawler->filter('a:contains("' . $this->container->get('translator')->trans('setting.translations.create.button', ['%locale%' => $this->client->getRequest()->getLocale()]) .'")')->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit valid form data.
        $form = $form->form();

        // Make sure, that the other language was pre-filled per default.
        $this->assertEquals('en', $form->get('fieldable_form[locale]')->getValue());

        $form['fieldable_form[f1]'] = 'Any';
        $form['fieldable_form[f2]'] = 'b';
        $this->client->submit($form);

        // Assert update
        $settingType = $this->em->getRepository('UniteCMSCoreBundle:SettingType')->find($this->domain->getSettingTypes()->first()->getId());
        $setting = $settingType->getSetting('de');
        $this->assertEquals('la', $setting->getData()['f1']);
        $this->assertEquals('b', $setting->getData()['f2']);

        // Assert that the new content was saved as translation for the original content.
        $setting_en = $settingType->getSetting('en');
        $this->assertEquals('en', $setting_en->getLocale());
        $this->assertEquals([
            'f1' => 'Any',
            'f2' => 'b',
            'f3' => null,
        ], $setting_en->getData());


        // Try to access translations page with valid setting id.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_setting_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
            'setting' => $setting->getId(),
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is no create translation link on this page anymore.
        $this->assertCount(0, $crawler->filter('a.uk-button:contains("Add translation")'));
    }

    public function testRevisionActions() {

        // Create setting.
        $setting = new Setting();
        $setting->setSettingType($this->domain->getSettingTypes()->first())->setData(['f1' => 'la', 'f2' => 'b']);
        $this->em->persist($setting);
        $this->em->flush($setting);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll());

        $revisions_url = $this->container->get('router')->generate('unitecms_core_setting_revisions', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
            'setting' => $setting->getId(),
        ]);

        // Try to get revisions page of unknown setting.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitecms_core_setting_revisions', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'setting_type' => $this->domain->getSettingTypes()->first()->getIdentifier(),
            'setting' => $doctrineUUIDGenerator->generate($this->em, $setting),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to get revisions page of known setting.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is one revision.
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody tr'));

        // Update setting.
        $setting = $this->em->getRepository('UniteCMSCoreBundle:Setting')->find($setting->getId());
        $setting->setData(['f1' => 'foo', 'f2' => 'a']);
        $this->em->flush();

        // Make sure, that there are 2 revisions.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertCount(2, $crawler->filter('.unite-card-table table tbody tr'));

        // Revert to version 1.
        $crawler = $this->client->click($crawler->filter('a:contains("' . $this->container->get('translator')->trans('setting.revisions.revert.button') .'")')->link());

        // Assert form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form
        $form = $form->form();
        $form['form[_token]'] = 'invalid';
        $crawler = $this->client->submit($form);

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains(" The CSRF token is invalid. Please try to resubmit the form.")'));

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($revisions_url));
        $crawler = $this->client->followRedirect();

        // Assert remove translation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Setting reverted.")'));

        // Compare values & make sure, that there are 3 revisions.
        $this->em->refresh($setting);
        $this->assertEquals(['f1' => 'la', 'f2' => 'b'], $setting->getData());
        $this->assertCount(3, $crawler->filter('.unite-card-table table tbody tr'));
    }
}
