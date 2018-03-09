<?php

namespace App;

use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Routing\Router;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CoreBundleIntegrationTest extends DatabaseAwareTestCase
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
     * @var User $user
     */
    private $user;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test Integration Domain",
    "identifier": "d1", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1"
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
        $this->organization->setTitle('O1')->setIdentifier('o1');

        $this->domain = $this->container->get(
          'united.cms.domain_definition_parser'
        )->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->user = new User();
        $this->user->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->user->setEmail('example@example.com')->setFirstname(
          'Domain Editor'
        )->setLastname('Example')->setPassword(
          $this->container->get('security.password_encoder')->encodePassword(
            $this->user,
            'password'
          )
        );
        $this->em->persist($this->user);
        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);
        $this->em->refresh($this->user);
    }

    private function login()
    {
        $token = new UsernamePasswordToken(
          $this->user,
          null,
          'main',
          $this->user->getRoles()
        );
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testSymfonyInitializing()
    {

        // Test initializing container.
        $realCacheDir = $this->container->getParameter('kernel.cache_dir');
        $this->container->get('filesystem')->remove($realCacheDir);
        $this->container->get('kernel')->shutdown();
        $this->container->get('kernel')->boot();


        // Test file logging.
        $this->assertTrue(
          $this->container->get('test.logger')->log(LogLevel::ERROR, 'Testlog')
        );

    }

    public function testCoreBundleIntegration()
    {

        $this->login();

        // Try to load domain overview.
        $crawler = $this->client->request(
          'GET',
          $this->container->get('router')->generate(
            'unitedcms_core_domain_view',
            [
              'organization' => $this->organization->getIdentifier(),
              'domain' => $this->domain->getIdentifier(),
            ]
          )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(
          0,
          $crawler->filter('.uk-nav-default')->count()
        );

        // Try to load API endpoint.
        $this->client->request(
          'POST',
          $this->container->get('router')->generate(
            'unitedcms_core_api',
            [
              'domain' => $this->domain->getIdentifier(),
              'organization' => $this->organization->getIdentifier(),
            ],
            Router::ABSOLUTE_URL
          ),
          [],
          [],
          [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
          ],
          json_encode(['query' => '{ __schema { types { name } } }'])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());

        $found = false;
        foreach ($response->data->__schema->types as $type) {
            if ($type->name == 'Ct1Content') {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testCollectionFieldBundleIntegration()
    {

        $this->login();

        $collection_field = new ContentTypeField();
        $collection_field->setIdentifier('ctf1')
          ->setType('collection')
          ->setTitle('CTF 1')
          ->setSettings(
            new FieldableFieldSettings(
              [
                'fields' => [
                  [
                    'title' => 'Sub Field 1',
                    'identifier' => 'sf1',
                    'type' => 'text',
                  ],
                ],
              ]
            )
          );
        $this->domain->getContentTypes()->first()->addField($collection_field);
        $this->em->flush();

        // Try to load content create page.
        $crawler = $this->client->request(
          'GET',
          $this->container->get('router')->generate(
            'unitedcms_core_content_create',
            [
              'organization' => $this->organization->getIdentifier(),
              'domain' => $this->domain->getIdentifier(),
              'content_type' => $this->domain->getContentTypes()
                ->first()
                ->getIdentifier(),
              'view' => 'all',
            ]
          )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(
          0,
          $crawler->filter('united-cms-collection-field')->count()
        );

        // Create first content.
        $content = new Content();
        $rand_value = 'val'.random_int(0, 1000);
        $content->setData(
          [
            'ctf1' => [
              [
                'sf1' => $rand_value,
              ],
            ],
          ]
        );
        $content->setContentType($this->domain->getContentTypes()->first());
        $this->em->persist($content);
        $this->em->flush();

        // Try to load API endpoint.
        $this->client->request(
          'POST',
          $this->container->get('router')->generate(
            'unitedcms_core_api',
            [
              'domain' => $this->domain->getIdentifier(),
              'organization' => $this->organization->getIdentifier(),
            ],
            Router::ABSOLUTE_URL
          ),
          [],
          [],
          [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
          ],
          json_encode(
            ['query' => 'query { findCt1 { result { ctf1 { sf1 } } } }']
          )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(
          $rand_value,
          $response->data->findCt1->result[0]->ctf1[0]->sf1
        );
    }

    public function testStorageBundleIntegration()
    {

        $this->login();

        $file_field = new ContentTypeField();
        $file_field->setIdentifier('ctf1')
          ->setType('file')
          ->setTitle('CTF 1')
          ->setSettings(
            new FieldableFieldSettings(
              [
                'file_types' => 'txt',
                'bucket' => [
                  'endpoint' => 'https://example.com',
                  'key' => 'XXX',
                  'secret' => 'XXX',
                  'bucket' => 'foo',
                ],
              ]
            )
          );
        $this->domain->getContentTypes()->first()->addField($file_field);
        $this->em->flush();

        // Try to load content create page.
        $crawler = $this->client->request(
          'GET',
          $this->container->get('router')->generate(
            'unitedcms_core_content_create',
            [
              'organization' => $this->organization->getIdentifier(),
              'domain' => $this->domain->getIdentifier(),
              'content_type' => $this->domain->getContentTypes()
                ->first()
                ->getIdentifier(),
              'view' => 'all',
            ]
          )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(
          0,
          $crawler->filter('united-cms-storage-file-field')->count()
        );

        // Create first content.
        $content = new Content();
        $content->setData(
          [
            'ctf1' => [
                'name' => 'cat.jpg',
                'size' => 1234,
                'type' => 'image/jpeg',
                'id' => 'XXX-XX-XXX',
            ],
          ]
        );
        $content->setContentType($this->domain->getContentTypes()->first());
        $this->em->persist($content);
        $this->em->flush();

        // Try to load API endpoint.
        $this->client->request(
          'POST',
          $this->container->get('router')->generate(
            'unitedcms_core_api',
            [
              'domain' => $this->domain->getIdentifier(),
              'organization' => $this->organization->getIdentifier(),
            ],
            Router::ABSOLUTE_URL
          ),
          [],
          [],
          [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHENTICATION_FALLBACK' => 'true',
          ],
          json_encode(
            ['query' => 'query { findCt1 { result { ctf1 { name, size, type, id, url } } } }']
          )
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(
          'cat.jpg',
          $response->data->findCt1->result[0]->ctf1->name
        );
    }
}