<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.08.18
 * Time: 17:38
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class WebhookFunctionalTest extends DatabaseAwareTestCase
{

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var MockHandler $mockHandler
     */
    private $mockHandler;

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
          "title": "Test controller webhook test",
          "identifier": "webhook_test",
          "content_types": [
            {
              "title": "Website",
              "identifier": "website",
              "fields": [
                  {
                    "title": "Text",
                    "identifier": "text",
                    "type": "text",
                    "settings": {}
                  },
                  {
                    "title": "LongText",
                    "identifier": "longtext",
                    "type": "textarea",
                    "settings": {}
                  }
              ],
              "webhooks": [
                  {
                    "query": "query { type, text, longtext  }",
                    "url": "http://www.example1.com",
                    "check_ssl": true,
                    "secret_key": "key1212494949494",
                    "action": "event == \"update\""
                  },
                  {
                    "query": "query { type, text, longtext }",
                    "url": "http://www.example.com",
                    "check_ssl": true,
                    "secret_key": "asdfasdf234234234234",
                    "action": "event == \"create\""
                  }
              ]
            }
          ],
          "setting_types": [
            {
              "title": "Setting",
              "identifier": "setting",
              "fields": [
                  {
                    "title": "Setting",
                    "identifier": "text",
                    "type": "text",
                    "settings": {}
                  }
              ],
              "webhooks": [
                  {
                    "query": "query { type, text }",
                    "url": "http://www.example.com",
                    "check_ssl": true,
                    "secret_key": "key12124949456",
                    "action": "event == \"delete\""
                  }
              ]
            }
          ],
          "domain_member_types": [
            {
              "title": "Editor",
              "identifier": "editor",
              "domain_member_label": "{accessor}",
              "fields": []
            },
            {
              "title": "Viewer",
              "identifier": "viewer",
              "domain_member_label": "{accessor}",
              "fields": []
            }
           ],
          "permissions": {
            "view domain": "true",
            "update domain": "false"
          }
        }';

    /**
     * @var User[]
     */
    private $users;

    private $userPassword = 'XXXXXXXXX';

    public function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler([
           new Response(200, []),
           new Response(200, [])
        ]);
        $handler = HandlerStack::create($this->mockHandler);
        $this->client = new Client(['handler' => $handler, 'verify' => false]);

        $d = new \ReflectionProperty(static::$container->get('unite.cms.webhook_manager'), 'client');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.webhook_manager'), $this->client);

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test password reset')->setIdentifier('password_reset');

        $org2 = new Organization();
        $org2->setTitle('Org2')->setIdentifier('org2_org2');

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
        $domainEditorDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('editor'));
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);
        $this->users['domain_editor']->addDomain($domainEditorDomainMember);

        foreach ($this->users as $key => $user) {
            $this->em->persist($this->users[$key]);
        }

        $this->em->flush();

        foreach ($this->users as $key => $user) {
            $this->em->refresh($this->users[$key]);
        }
    }

    /**
     * Test webhooks on ContentType
     */
    public function testContentTypeWebhooks()
    {

        $ct = $this->domain->getContentTypes()->first();

        $content = new Content();
        $content->setContentType($ct);

        $content_data = [
            'text' => "my text",
            'longtext' => "my longtext"
        ];

        $content->setData($content_data);
        $this->em->persist($content);
        $this->em->flush($content);

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals(sha1('asdfasdf234234234234'), $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('{"type":"website","text":"my text","longtext":"my longtext"}', $this->mockHandler->getLastRequest()->getBody()->getContents());

        $content_data = [
          'text' => "my text 1",
          'longtext' => "my longtext 1"
        ];

        $content->setData($content_data);
        $this->em->flush($content);

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals(sha1('key1212494949494'), $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('{"type":"website","text":"my text 1","longtext":"my longtext 1"}', $this->mockHandler->getLastRequest()->getBody()->getContents());
       
    }

    /**
     * Test webhooks on SettingType
     */
    public function testSettingTypeWebhooks()
    {
        $st = $this->domain->getSettingTypes()->first();
        $setting = new Setting();
        $setting->setSettingType($st);

        $setting_data = [
          'text' => "my text",
        ];

        $setting->setData($setting_data);
        $this->em->persist($setting);
        $this->em->flush($setting);
        $this->em->refresh($setting);

        $this->em->remove($setting);
        $this->em->flush();

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals(sha1('key12124949456'), $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('{"type":"setting","text":"my text"}', $this->mockHandler->getLastRequest()->getBody()->getContents());
    }
}