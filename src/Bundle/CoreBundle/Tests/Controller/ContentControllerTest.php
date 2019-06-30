<?php

namespace UniteCMS\CoreBundle\Tests\Controller;

use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

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
      },
      {
        "title": "CT 2 (Referenced entity)",
        "identifier": "ct2",
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }
        ],
        "views": [
            { "title": "All", "identifier": "all", "type": "table" }
        ],
        "locales": ["de", "en"]
      },
      {
        "title": "CT 3 (Required reference field test)",
        "identifier": "ct3",
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" },
            { "title": "Field 2", "identifier": "f2", "type": "choice", "settings": { "choices": ["a", "b"] } },
            { "title": "Field 4", "identifier": "f3", "type": "reference", "settings": { "domain": "d1", "content_type": "ct2", "not_empty": true } }
        ],
        "views": [
            { "title": "All", "identifier": "all", "type": "table" }
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
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
        $this->client->disableReboot();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1_org1');
        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->editor = new User();
        $this->editor->setEmail('editor@example.com')->setName('Domain Editor')->setRoles([User::ROLE_USER])->setPassword('XXX');
        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setOrganization($this->organization);

        $domainEditorDomainMemberViewer = new DomainMember();
        $domainEditorDomainMemberViewer->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('viewer'));

        $domainEditorDomainMemberEditor = new DomainMember();
        $domainEditorDomainMemberEditor->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('editor'));

        $this->editor->addOrganization($domainEditorOrgMember);
        $this->editor->addDomain($domainEditorDomainMemberViewer);
        $this->editor->addDomain($domainEditorDomainMemberEditor);

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

        $url_other_list = static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'other',
        ], Router::ABSOLUTE_URL);

        $this->client->request('GET', $url_other_list);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $url_list = static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', $url_list);

        // Assert view rendering.
        $view = $crawler->filter('unite-cms-core-view-table');
        $this->assertCount(1, $view);
        $viewData = json_decode($view->attr('parameters'));

        // Click on add button.
        $crawler = $this->client->request('GET', $viewData->urls->create);

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
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([ 'contentType' => $this->domain->getContentTypes()->first(), ], [ 'created' => 'DESC', ]);
        $this->assertNotNull($content);
        $this->assertEquals('Field value 1', $content->getData()['f1']);
        $this->assertEquals('a', $content->getData()['f2']);

        // Try to update invalid content
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_update', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to update valid content
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_update', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId()
        ], Router::ABSOLUTE_URL));
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
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_delete', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to delete valid content
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_delete', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId()
        ], Router::ABSOLUTE_URL));
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
        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // Make sure, that the content was deleted.
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');
    }

    public function testContentValidation() {

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // On Create.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_create', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL));
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
        $error_text = static::$container->get('translator')->trans('required', [], 'validators');
        $alert = $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p');
        $this->assertCount(1, $alert);
        $this->assertEquals($error_text, $alert->text());

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
        $error_text = static::$container->get('translator')->trans('invalid_reference_definition', [], 'validators');
        $alert = $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p');
        $this->assertCount(1, $alert);
        $this->assertEquals($error_text, $alert->text());

        // On Update.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_update', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
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
        $error_text = static::$container->get('translator')->trans('required', [], 'validators');
        $alert = $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p');
        $this->assertCount(1, $alert);
        $this->assertEquals($error_text, $alert->text());

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
        $error_text = static::$container->get('translator')->trans('invalid_reference_definition', [], 'validators');
        $alert = $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p');
        $this->assertCount(1, $alert);
        $this->assertEquals($error_text, $alert->text());
    }

    public function testDeleteDefinitelyAction() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // Try to definitely delete unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_deletedefinitely', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to definitely delete non-deleted content.
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_deletedefinitely', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Delete content.
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([
            'contentType' => $this->domain->getContentTypes()->first()->getId(),
        ]);
        $this->em->remove($content);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Try to access page without UPDATE right.
        $ct = $this->em->getRepository('UniteCMSCoreBundle:ContentType')->find($this->domain->getContentTypes()->first()->getId());
        $ct->addPermission(ContentVoter::UPDATE, 'false');
        $this->em->flush($ct);

        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_deletedefinitely', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $ct->addPermission(ContentVoter::UPDATE, 'true');
        $this->em->flush($ct);

        // Delete content definitely.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_deletedefinitely', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
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
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert deletion message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content deleted.")'));

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

    }

    public function testDeleteRecoverAction() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // Try to recover unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_recover', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to recover non-deleted content.
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_recover', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Delete content.
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([
            'contentType' => $this->domain->getContentTypes()->first()->getId(),
        ]);
        $this->em->remove($content);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Try to access page without UPDATE right.
        $ct = $this->em->getRepository('UniteCMSCoreBundle:ContentType')->find($this->domain->getContentTypes()->first()->getId());
        $ct->addPermission(ContentVoter::UPDATE, 'false');
        $this->em->flush($ct);

        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_recover', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $ct->addPermission(ContentVoter::UPDATE, 'true');
        $this->em->flush($ct);

        // Recover delete content.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_recover', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Assert recover form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form
        $form = $form->form();
        $form['form[_token]'] = 'invalid';
        $crawler = $this->client->submit($form);

        // For performance reasons we do not reboot the kernel on each request, so we need to clear em by hand.
        $this->em->clear();

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains(" The CSRF token is invalid. Please try to resubmit the form.")'));

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $this->client->submit($form);

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert recover message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains(" Deleted content was restored.")'));
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
    }

    public function testTranslateActions() {

        // Create content.
        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first())->setData(['f1' => 'la', 'f2' => 'b'])->setLocale('de');
        $this->em->persist($content);
        $this->em->flush($content);
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // Try to access translations page with invalid content id.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to access translations page with valid content id.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));

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

        // For performance reasons we do not reboot the kernel on each request, so we need to clear em by hand.
        $this->em->clear();

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert recover message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains(" Content created.")'));
        $this->assertCount(2, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        // Assert that the new content was saved as translation for the original content.
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($content->getId());
        $this->assertCount(1, $content->getTranslations());
        $translated_content = $content->getTranslations()->first();
        $this->assertEquals('en', $translated_content->getLocale());
        $this->assertEquals([
            'f1' => 'Any',
            'f2' => 'b',
            'f3' => null,
        ], $translated_content->getData());


        // Try to access translations page with valid content id.
        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL));

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is no create translation link on this page anymore.
        $this->assertCount(0, $crawler->filter('a.uk-button:contains("Create Translation")'));

        // Remove original content.
        $this->em->remove($content);
        $this->em->flush();
        $this->em->clear();

        // Try to access translation page of soft-deleted content.
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $translated_content->getId(),
        ], Router::ABSOLUTE_URL));
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert warning.
        $this->assertCount(1, $crawler->filter('.uk-alert-warning:contains("You cannot manage translations for this content, because it is a translation of soft-deleted content.")'));

        // Recover content.
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType()]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $content->recoverDeleted();
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $translated_content->getId(),
        ], Router::ABSOLUTE_URL));

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

        // For performance reasons we do not reboot the kernel on each request, so we need to clear em by hand.
        $this->em->clear();

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert remove translation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Translation removed.")'));

        // Both content should stay present, however they are not linked as translation anymore.
        $this->assertCount(2, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType()]);
        $translated_content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(['id' => $translated_content->getId(), 'contentType' => $content->getContentType()]);
        $this->assertCount(0, $content->getTranslations());
        $this->assertNull($translated_content->getTranslationOf());

        // Link existing content was translation.
        $crawler = $this->client->click($crawler->filter('a:contains("' . static::$container->get('translator')->trans('content.translations.add_existing.button') .'")')->link());

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
        $this->assertCount(1, $crawler->filter('.uk-alert-danger:contains("'.static::$container->get('translator')->trans('translation_content_not_found', [], 'validators').'")'));

        // Submit valid form
        $form = $crawler->filter('form');
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['form']['translation'] = [
            'content' => $translated_content->getId(),
        ];
        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect(static::$container->get('router')->generate('unitecms_core_content_translations', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL)));
        $crawler = $this->client->followRedirect();

        // Assert remove translation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Translation added.")'));

        // Assert that the translated content was added as translation
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($content->getId());
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
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll());

        $revisions_url = static::$container->get('router')->generate('unitecms_core_content_revisions', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $content->getId(),
        ], Router::ABSOLUTE_URL);

        // Try to get revisions page of unknown content.
        $doctrineUUIDGenerator = new UuidGenerator();
        $this->client->request('GET', static::$container->get('router')->generate('unitecms_core_content_revisions', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->first()->getIdentifier(),
            'view' => 'all',
            'content' => $doctrineUUIDGenerator->generate($this->em, $content),
        ], Router::ABSOLUTE_URL));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());

        // Try to get revisions page of known content.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that there is one revision.
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody tr'));

        // Update content.
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($content->getId());
        $content->setData(['f1' => 'foo', 'f2' => 'a']);
        $this->em->flush();

        // Make sure, that there are 2 revisions.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertCount(2, $crawler->filter('.unite-card-table table tbody tr'));

        // Revert to version 1.
        $crawler = $this->client->click($crawler->filter('a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")')->link());

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
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(['id' => $content->getId(), 'contentType' => $content->getContentType() ]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // Recover content.
        $content->recoverDeleted();
        $this->client->reload();
        $this->em->flush();
        $this->em->clear();

        // There should be an entry for the remove and the recover actions.
        $crawler = $this->client->request('GET', $revisions_url);
        $this->assertCount(5, $crawler->filter('.unite-card-table table tbody tr'));
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody td:contains("remove")'));
        $this->assertCount(1, $crawler->filter('.unite-card-table table tbody td:contains("recover")'));

        // And delete should not have a recover action.
        $this->assertCount(1, $crawler->filter('tr:nth-child(5) a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(1, $crawler->filter('tr:nth-child(4) a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(1, $crawler->filter('tr:nth-child(3) a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(0, $crawler->filter('tr:nth-child(2) a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")'));
        $this->assertCount(0, $crawler->filter('tr:nth-child(1) a:contains("' . static::$container->get('translator')->trans('content.revisions.revert.button') .'")'));
    }

    public function testNotEmptyReference() {

        $ct2 = $this->domain->getContentTypes()->get('ct2');

        $c2 = new Content();
        $c2->setContentType($ct2)->setData(['f1' => 'abcd']);
        $this->em->persist($c2);
        $this->em->flush();

        $url_list = static::$container->get('router')->generate('unitecms_core_content_index', [
            'organization' => IdentifierNormalizer::denormalize($this->organization->getIdentifier()),
            'domain' => $this->domain->getIdentifier(),
            'content_type' => $this->domain->getContentTypes()->get('ct3')->getIdentifier(),
            'view' => 'all',
        ], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', $url_list);

        // Assert view rendering.
        $view = $crawler->filter('unite-cms-core-view-table');
        $this->assertCount(1, $view);
        $viewData = json_decode($view->attr('parameters'));

        // Click on add button.
        $crawler = $this->client->request('GET', $viewData->urls->create);

        // Assert add form
        $form = $crawler->filter('form');
        $this->assertCount(1, $form);

        // Submit invalid form data - missing not_empty reference content.
        $form = $form->form();
        $values = $form->getPhpValues();
        $values['fieldable_form']['f1'] = 'Field value 1';
        $values['fieldable_form']['f2'] = 'a';
        $values['fieldable_form']['f3'] = [
            'domain' => 'd1',
            'content_type' => 'ct2',
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Should stay on the same page.
        $this->assertFalse($this->client->getResponse()->isRedirection());
        $this->assertCount(1, $crawler->filter('#fieldable_form_f3 + .uk-alert-danger p:contains("This field is required.")'));


        // Submit valid form data
        $values = $form->getPhpValues();
        $values['fieldable_form']['f1'] = 'Field value 1';
        $values['fieldable_form']['f2'] = 'a';
        $values['fieldable_form']['f3'] = [
            'domain' => 'd1',
            'content_type' => 'ct2',
            'content' => $c2->getId(),
        ];
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        // Assert redirect to index.
        $this->assertTrue($this->client->getResponse()->isRedirect($url_list));
        $crawler = $this->client->followRedirect();

        // Assert creation message.
        $this->assertCount(1, $crawler->filter('.uk-alert-success:contains("Content created.")'));
    }
}
