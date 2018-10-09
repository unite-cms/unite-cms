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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
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
                    "query": "query { type, text, longtext }",
                    "url": "http://www.example1.com",
                    "condition": "event == \"create\""
                  },
                  {
                    "query": "query { type, text, longtext  }",
                    "url": "http://www.example2.com",
                    "check_ssl": true,
                    "authentication_header": "key1212494949494",
                    "condition": "event == \"update\""
                  },
                  {
                    "query": "query { type, text }",
                    "url": "http://www.example3.com",
                    "check_ssl": true,
                    "authentication_header": "key1212494949494",
                    "condition": "event == \"delete\""
                  }
              ],
              "locales": [
                  "en",
                  "de"
              ]
            },
            {
              "title": "Website2",
              "identifier": "website2",
              "fields": [
                  {
                    "title": "Choice",
                    "identifier": "choice",
                    "type": "choice",
                    "settings": {
                        "choices": {
                            "Red color": "red",
                            "Green color": "green",
                            "Blue color": "blue"
                        }
                    }
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
                    "query": "query { type, choice, longtext }",
                    "url": "http://www.example4.com",
                    "content_type": "form_data",
                    "condition": "event == \"create\""
                  },
                  {
                    "query": "query { type, choice, longtext  }",
                    "url": "http://www.example5.com",
                    "check_ssl": true,
                    "content_type": "form_data",
                    "authentication_header": "key1212",
                    "condition": "event == \"update\""
                  }
              ],
              "locales": [
                  "en",
                  "de"
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
                    "url": "http://www.example6.com",
                    "check_ssl": true,
                    "authentication_header": "key121277543",
                    "condition": "event == \"update\""
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

    public function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler([
            new Response(200, []),
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
        $this->organization->setTitle('Test wekhooks')->setIdentifier('webhook_test');

        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();

    }

    /**
     * Test webhooks on ContentType with Json content type
     */
    public function testContentTypeWebhooksJson()
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
        $this->assertEquals([], $this->mockHandler->getLastRequest()->getHeader('Authorization'));
        $this->assertEquals('{"type":"website","text":"my text","longtext":"my longtext"}', $this->mockHandler->getLastRequest()->getBody()->getContents());

        $content_data = [
          'text' => "my text 1",
          'longtext' => "my longtext 1"
        ];

        $content->setData($content_data);
        $this->em->flush($content);

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals('key1212494949494', $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('{"type":"website","text":"my text 1","longtext":"my longtext 1"}', $this->mockHandler->getLastRequest()->getBody()->getContents());

        $this->em->refresh($content);

        $this->em->remove($content);
        $this->em->flush();

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals('key1212494949494', $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('', $this->mockHandler->getLastRequest()->getBody()->getContents());
       
    }

    /**
     * Test webhooks on ContentType with form data content type
     */
    public function testContentTypeWebhooksFormData()
    {
        $ct = $this->domain->getContentTypes()->last();

        $content = new Content();
        $content->setContentType($ct);

        $content_data = [
            'choice' => "red",
            'longtext' => "my longtext 123"
        ];

        $content->setData($content_data);
        $this->em->persist($content);
        $this->em->flush($content);

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals([], $this->mockHandler->getLastRequest()->getHeader('Authorization'));
        $this->assertEquals('type=website2&choice=red&longtext=my+longtext+123', $this->mockHandler->getLastRequest()->getBody()->getContents());

        $content_data = [
            'choice' => "green",
            'longtext' => "my longtext 123"
        ];

        $content->setData($content_data);
        $this->em->flush($content);

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals('key1212', $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('type=website2&choice=green&longtext=my+longtext+123', $this->mockHandler->getLastRequest()->getBody()->getContents());

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

        $this->assertNotNull($this->mockHandler->getLastRequest());
        $this->assertEquals('key121277543', $this->mockHandler->getLastRequest()->getHeader('Authorization')[0]);
        $this->assertEquals('{"type":"setting","text":"my text"}', $this->mockHandler->getLastRequest()->getBody()->getContents());
    }
}