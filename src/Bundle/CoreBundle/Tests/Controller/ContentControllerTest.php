<?php

namespace UnitedCMS\CoreBundle\Tests\Controller;

use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ContentControllerTest extends DatabaseAwareTestCase {

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
            { "title": "Field 2", "identifier": "f2", "type": "choice", "settings": { "choices": ["a", "b"] } },
            { "title": "Field 3", "identifier": "f3", "type": "reference", "settings": { "domain": "d1", "content_type": "ct1" } }
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
        $this->domain = $this->container->get('united.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->editor = new User();
        $this->editor->setEmail('editor@example.com')->setFirstname('Domain Editor')->setLastname('Example')->setRoles([User::ROLE_USER])->setPassword('XXX');
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

        $url_other_list = $this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'other',
        ]);

        $this->client->request('GET', $url_other_list);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $url_list = $this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ]);

        $crawler = $this->client->request('GET', $url_list);

        // Assert add button.
        $addButton = $crawler->filter('header .uk-button-primary');
        $this->assertCount(1, $addButton);

        // Click on add button.
        $crawler = $this->client->click($addButton->first()->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data.
        $form = $form->form();
        $form->disableValidation();
        $form['fieldable_form[f1]'] = 'Field value 1';
        $form['fieldable_form[f2]'] = 'fr';
        $form['fieldable_form[locale]'] = 'unknown';
        $crawler = $this->client->submit($form);

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_locale + .uk-alert-danger p:contains("This value is not valid.")'));
        $this->assertCount(1, $crawler->filter('#fieldable_form_f2 + .uk-alert-danger p:contains("This value is not valid.")'));


        // Submit valid form data
        $form = $crawler->filter('form');
        $form = $form->form();
        $form['fieldable_form[f1]'] = 'Field value 1';
        $form['fieldable_form[f2]'] = 'a';
        $form['fieldable_form[locale]'] = 'de';
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content created.")'));

        // Since the view list is rendered in js, we can't check creation via DOM. But we can see, if we can edit
        // the content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([ 'contentType' => $this->domain->getContentTypes()->first(), ], [ 'created' => 'DESC', ]);
        $this->assertNotNull($content);
        $this->assertEquals('Field value 1', $content->getData()['f1']);
        $this->assertEquals('a', $content->getData()['f2']);

        // Try to update invalid content
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_update', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to update valid content
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_update', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId()
        ]));
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

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content updated.")'));

        // Update content.
        $this->em->refresh($content);
        $this->assertEquals('Updated Field value 1', $content->getData()['f1']);
        $this->assertEquals('b', $content->getData()['f2']);


        // Try to delete invalid content.
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_delete', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to delete valid content
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_delete', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId()
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert add form
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
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert deletion message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content deleted.")'));
        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // Make sure, that the content was deleted.
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');
    }

    public function testContentValidation() {

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // On Create.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_create', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
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
            'domain' => 'foo',
            'content_type' => 'baa',
            'content' => 'any',
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p:contains("validation.wrong_definition")'));

        // On Update.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_update', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
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
            'domain' => 'foo',
            'content_type' => 'baa',
            'content' => 'any',
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p:contains("validation.wrong_definition")'));
    }

    public function testDeleteDefinitelyAction() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // Try to definitely delete unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_deletedefinitely', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to definitely delete non-deleted content.
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_deletedefinitely', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Delete content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
            'contentType' => $this->domain->getContentTypes()->first()->getId(),
        ]);
        $this->em->remove($content);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Try to access page without UPDATE right.
        $ct = $this->em->getRepository('UnitedCMSCoreBundle:ContentType')->find($this->domain->getContentTypes()->first()->getId());
        $ct->addPermission(ContentVoter::UPDATE, [Domain::ROLE_ADMINISTRATOR]);
        $this->em->flush($ct);

        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_deletedefinitely', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $ct->addPermission(ContentVoter::UPDATE, [Domain::ROLE_EDITOR]);
        $this->em->flush($ct);

        // Delete content definitely.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_deletedefinitely', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert delete form
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
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ])));
        $crawler = $this->client->followRedirect();

        // Assert deletion message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content deleted.")'));

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

    }

    public function testDeleteRecoverAction() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // Try to recover unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_recover', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to recover non-deleted content.
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_recover', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Delete content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy([
            'contentType' => $this->domain->getContentTypes()->first()->getId(),
        ]);
        $this->em->remove($content);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Try to access page without UPDATE right.
        $ct = $this->em->getRepository('UnitedCMSCoreBundle:ContentType')->find($this->domain->getContentTypes()->first()->getId());
        $ct->addPermission(ContentVoter::UPDATE, [Domain::ROLE_ADMINISTRATOR]);
        $this->em->flush($ct);

        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_recover', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $ct->addPermission(ContentVoter::UPDATE, [Domain::ROLE_EDITOR]);
        $this->em->flush($ct);

        // Recover delete content.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_recover', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert recover form
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
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ])));
        $crawler = $this->client->followRedirect();

        // Assert recover message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains(" Deleted content was restored.")'));
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
    }

    public function testTranslateActions() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // Try to access translations page with invalid content id.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to access translations page with valid content id.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Create english translation.
        $crawler = $this->client->click($crawler->filter('a.uk-text-success')->link());

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

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ])));
        $crawler = $this->client->followRedirect();

        // Assert recover message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains(" Content created.")'));
        $this->assertCount(2, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        // Assert that the new content was saved as translation for the original content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->find($content->getId());
        $this->assertCount(1, $content->getTranslations());
        $translated_content = $content->getTranslations()->first();
        $this->assertEquals('en', $translated_content->getLocale());
        $this->assertEquals([
            'f1' => 'Any',
            'f2' => 'b',
            'f3' => null,
        ], $translated_content->getData());


        // Try to access translations page with valid content id.
        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is no create translation link on this page anymore.
        $this->assertCount(0, $crawler->filter('a.uk-button:contains("Create Translation")'));

        // Remove original content.
        $this->em->remove($content);
        $this->em->flush();
        $this->em->clear();

        // Try to access translation page of soft-deleted content.
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $translated_content->getId(),
        ]));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_index', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ])));
        $crawler = $this->client->followRedirect();

        // Assert warning.
        $this->assertCount(1, $crawler->filter('.uk-alert-warning:contains("You cannot manage translations for this content, because it is a translation of soft-deleted content.")'));

        // Recover content.
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType()]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $content->recoverDeleted();
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $translated_content->getId(),
        ]));

        // Remove english translation.
        $crawler = $this->client->click($crawler->filter('a.uk-text-danger:contains("Remove as translation")')->link());

        // Assert remove translation form
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
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ])));
        $crawler = $this->client->followRedirect();

        // Assert remove translation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Translation removed.")'));

        // Both content should stay present, however they are not linked as translation anymore.
        $this->assertCount(2, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType()]);
        $translated_content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['id' => $translated_content->getId(), 'contentType' => $content->getContentType()]);
        $this->assertCount(0, $content->getTranslations());
        $this->assertNull($translated_content->getTranslationOf());

        // Link existing content was translation.
        $crawler = $this->client->click($crawler->filter('a:contains("' . $this->container->get('translator')->trans('content.translations.add_existing.button') .'")')->link());

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data.
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['form']['translation'] = [
            'content' => $doctrineUUIDGenerator->generate($this->em, $translated_content),
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("validation.content_not_found")'));

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['form']['translation'] = [
            'content' => $translated_content->getId(),
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($this->container->get('router')->generate('unitedcms_core_content_translations', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ])));
        $crawler = $this->client->followRedirect();

        // Assert remove translation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Translation added.")'));

        // Assert that the translated content was added as translation
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->find($content->getId());
        $this->assertCount(1, $content->getTranslations());
        $this->assertEquals('en', $content->getTranslations()->first()->getLocale());
        $this->assertEquals($translated_content->getId(), $content->getTranslations()->first()->getId());
    }

    public function testRevisionActions() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b']);
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Content')->findAll());

        $revisions_url = $this->container->get('router')->generate('unitedcms_core_content_revisions', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ]);

        // Try to get revisions page of unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', $this->container->get('router')->generate('unitedcms_core_content_revisions', [
            'organization' => $this->organization->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to get revisions page of known content.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is one revision.
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody tr'));

        // Update content.
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->find($content->getId());
        $content->setData(['f1' => 'foo', 'f2' => 'a']);
        $this->em->flush();

        // Make sure, that there are 2 revisions.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertCount(2, $crawler->filter('.unite-card-table table tbody tr'));

        // Revert to version 1.
        $crawler = $this->client->click($crawler->filter('a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")')->link());

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
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content reverted.")'));

        // Compare values & make sure, that there are 3 revisions.
        $this->em->refresh($content);
        $this->assertEquals(['f1' => 'la', 'f2' => 'b'], $content->getData());
        $this->assertCount(3, $crawler->filter('.unite-card-table table tbody tr'));

        // Delete content.
        $this->em->remove($content);
        $this->em->flush();
        $this->em->clear();

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UnitedCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType() ]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Recover content.
        $content->recoverDeleted();
        $this->em->flush();
        $this->em->clear();

        // There should be an entry for the remove and the recover actions.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertCount(5, $crawler->filter('.unite-card-table table tbody tr'));
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody td:contains("remove")'));
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody td:contains("recover")'));

        // And delete should not have a recover action.
        $this->assertCount(1, $crawler->filter('tr:nth-child(5) a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(1, $crawler->filter('tr:nth-child(4) a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(1, $crawler->filter('tr:nth-child(3) a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(0, $crawler->filter('tr:nth-child(2) a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(0, $crawler->filter('tr:nth-child(1) a:contains("' . $this->container->get('translator')->trans('content.revisions.revert.button') .'")'));
    }
}