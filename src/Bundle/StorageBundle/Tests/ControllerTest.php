<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 08.02.18
 * Time: 09:19
 */

namespace UniteCMS\StorageBundle\Tests;

use Aws\S3\S3Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;
use UniteCMS\StorageBundle\Form\PreSignFormType;
use UniteCMS\StorageBundle\Model\PreSignedUrl;

class ControllerTest extends DatabaseAwareTestCase
{

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var $token
     */
    private $csrf_token;

    /**
     * @var User $user
     */
    private $user;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Domain 1",
    "identifier": "d1", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1", 
        "fields": [
            { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } } },
            { "title": "Nested", "identifier": "nested", "type": "collection", "settings": 
              { "fields": [
                { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } } }
              ]}
            }
        ]
      },
      {
        "title": "CT 2",
        "identifier": "ct2", 
        "fields": [
            { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } } },
            { "title": "Nested", "identifier": "nested", "type": "collection", "settings": 
              { "fields": [
                { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } } }
              ]}
            }
        ],
        "permissions": {
            "create content": "false"
        }
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1", 
        "fields": [
            { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } }  }
        ]
      },
      {
        "title": "ST 2",
        "identifier": "st2", 
        "fields": [
            { "title": "File", "identifier": "file", "type": "file", "settings": { "file_types": "txt", "bucket": { "endpoint": "https://example.com", "key": "XXX", "secret": "XXX", "bucket": "foo" } }  }
        ],
        "permissions": {
            "update setting": "false"
        }
      }
    ]
  }';

    /**
     * @var Organization $org1
     */
    private $org1;

    /**
     * @var Domain $domain1
     */
    private $domain1;

    public function setUp()
    {
        parent::setUp();

        $this->org1 = new Organization();
        $this->org1->setIdentifier('org1')->setTitle('org1');
        $this->em->persist($this->org1);
        $this->em->flush($this->org1);

        $this->domain1 = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain1->setOrganization($this->org1);
        $this->em->persist($this->domain1);
        $this->em->flush($this->domain1);

        $editor = new User();
        $editor
            ->setEmail('editor@example.com')
            ->setName('Editor')
            ->setPassword('XXX')
            ->setRoles([User::ROLE_USER]);

        $editorMember = new OrganizationMember();
        $editorMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org1);
        $editorDomainMember = new DomainMember();
        $editorDomainMember->setDomain($this->domain1)->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('editor'));
        $editor->addOrganization($editorMember);
        $editor->addDomain($editorDomainMember);
        $this->em->persist($editor);
        $this->em->flush($editor);
        $this->user = $editor;

        $this->client = $this->container->get('test.client');
        $this->client->followRedirects(false);

    }

    public function testPreSignFileUploadWithApiFirewall()
    {
        # generate new csrf_token
        $this->csrf_token = $this->container->get('security.csrf.token_manager')->getToken(
            StringUtil::fqcnToBlockPrefix(PreSignFormType::class)
        );

        $apiClient = new ApiKey();
        $apiClient->setOrganization($this->org1);
        $domainMember = new DomainMember();
        $domainMember->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('editor'))->setDomain($this->domain1);
        $apiClient
            ->setName('API Client 1')
            ->setToken('abc')
            ->addDomain($domainMember);
        $this->em->persist($apiClient);
        $this->em->flush($apiClient);

        $route_uri = $this->container->get('router')->generate(
            'unitecms_storage_sign_uploadcontenttype',
            [
                'domain' => $this->domain1->getIdentifier(),
                'organization' => $this->org1->getIdentifier(),
                'content_type' => 'ct1',
                'token' => $apiClient->getToken(),
            ],
            Router::ABSOLUTE_URL
        );

        $parameters = [
            'pre_sign_form' => [
                'field' => 'file',
                'filename' => 'ö Aä.*#ä+ .txt',
            ],
        ];

        $this->client->request(
            'POST',
            $route_uri,
            $parameters,
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['query' => '{}'])
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testPreSignFileUploadWithMainFirewall()
    {

        $token = new UsernamePasswordToken($this->user, null, 'main', $this->user->getRoles());

        # generate new csrf_token
        $this->csrf_token = $this->container->get('security.csrf.token_manager')->getToken(
            StringUtil::fqcnToBlockPrefix(PreSignFormType::class)
        );

        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
        $this->client->setServerParameter('HTTP_Authentication-Fallback', true);

        // Try to access with invalid method.
        $baseUrl = $this->container->get('router')->generate(
            'unitecms_storage_sign_uploadcontenttype',
            ['organization' => 'foo', 'domain' => 'baa', 'content_type' => 'foo']
        );
        $this->client->request('GET', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
        $this->client->request('PUT', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
        $this->client->request('DELETE', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());

        $baseUrl = $this->container->get('router')->generate(
            'unitecms_storage_sign_uploadsettingtype',
            ['organization' => 'foo', 'domain' => 'baa', 'setting_type' => 'foo']
        );
        $this->client->request('GET', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
        $this->client->request('PUT', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
        $this->client->request('DELETE', $baseUrl);
        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());

        // Try to pre sign for invalid organization domain content type and setting type.
        foreach ([
                     ['organization' => 'foo', 'domain' => 'baa', 'content_type' => 'foo'],
                     ['organization' => $this->org1->getIdentifier(), 'domain' => 'baa', 'content_type' => 'foo'],
                     [
                         'organization' => $this->org1->getIdentifier(),
                         'domain' => $this->domain1->getIdentifier(),
                         'content_type' => 'foo',
                     ],
                 ] as $params) {

            $this->client->request(
                'POST',
                $this->container->get('router')->generate('unitecms_storage_sign_uploadcontenttype', $params),
                []
            );
            $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        }

        foreach ([
                     ['organization' => 'foo', 'domain' => 'baa', 'setting_type' => 'foo'],
                     ['organization' => $this->org1->getIdentifier(), 'domain' => 'baa', 'setting_type' => 'foo'],
                     [
                         'organization' => $this->org1->getIdentifier(),
                         'domain' => $this->domain1->getIdentifier(),
                         'setting_type' => 'foo',
                     ],
                 ] as $params) {
            $this->client->request(
                'POST',
                $this->container->get('router')->generate('unitecms_storage_sign_uploadsettingtype', $params),
                []
            );
            $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        }

        // Try to pre sign without CREATE permission.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct2',
                ]
            )
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st2',
                ]
            )
        );
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Try to pre sign for invalid content type field.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'foo'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Try to pre sign for invalid setting type field.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'foo'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Try to pre sign for invalid content type nested field.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'foo/baa'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'nested/baa'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Try to pre sign for invalid setting type nested field.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'foo/baa'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st1',
                ]
            ),
            [
                'pre_sign_form' => ['field' => 'nested/baa'],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Try to pre sign invalid file type.
        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            ),
            [
                'pre_sign_form' => [
                    'field' => 'file',
                    'filename' => 'unsupported.unsupported',
                ],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st1',
                ]
            ),
            [
                'pre_sign_form' => [
                    'field' => 'file',
                    'filename' => 'unsupported.unsupported',
                ],
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

        // Try to pre sign filename with special chars.

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadcontenttype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            ),
            [
                'pre_sign_form' => [
                    'field' => 'file',
                    'filename' => 'ö Aä.*#ä+ .txt',
                    '_token' => $this->csrf_token->getValue(),
                ],
            ]
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = \GuzzleHttp\json_decode($this->client->getResponse()->getContent());

        // Check checksum.
        $preSignedUrl = new PreSignedUrl(
            $response->pre_signed_url,
            $response->uuid,
            $response->filename,
            $response->checksum
        );
        $this->assertNotNull($preSignedUrl->getChecksum());
        $this->assertTrue($preSignedUrl->check($this->container->getParameter('kernel.secret')));

        $s3Client = new S3Client(
            [
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => 'https://example.com',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => 'XXX',
                    'secret' => 'XXX',
                ],
            ]
        );

        $command = $s3Client->getCommand(
            'PutObject',
            [
                'Bucket' => 'foo',
                'Key' => $preSignedUrl->getUuid().'/_a._.txt',
            ]
        );

        $presignedRequest = $s3Client->createPresignedRequest($command, '+5 minutes');

        $newResponse = \GuzzleHttp\json_decode($this->client->getResponse()->getContent());

        $generatedParts = explode('&X-Amz-Date=', $newResponse->pre_signed_url);
        $actualParts = explode('&X-Amz-Date=', (string)$presignedRequest->getUri());

        $this->assertEquals($actualParts[0], $generatedParts[0]);

        $this->client->request(
            'POST',
            $this->container->get('router')->generate(
                'unitecms_storage_sign_uploadsettingtype',
                [
                    'organization' => $this->org1->getIdentifier(),
                    'domain' => $this->domain1->getIdentifier(),
                    'setting_type' => 'st1',
                ]
            ),
            [
                'pre_sign_form' => [
                    'field' => 'file',
                    'filename' => 'ö Aä.*#ä+ .txt',
                    '_token' => $this->csrf_token->getValue(),
                ],
            ]
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = \GuzzleHttp\json_decode($this->client->getResponse()->getContent());

        $preSignedUrl = new PreSignedUrl(
            $response->pre_signed_url,
            $response->uuid,
            $response->filename,
            $response->checksum
        );
        $this->assertNotNull($preSignedUrl->getChecksum());
        $this->assertTrue($preSignedUrl->check($this->container->getParameter('kernel.secret')));

        $s3Client = new S3Client(
            [
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => 'https://example.com',
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => 'XXX',
                    'secret' => 'XXX',
                ],
            ]
        );

        $command = $s3Client->getCommand(
            'PutObject',
            [
                'Bucket' => 'foo',
                'Key' => $preSignedUrl->getUuid().'/_a._.txt',
            ]
        );

        $presignedRequest = $s3Client->createPresignedRequest($command, '+5 minutes');
        $newResponse = \GuzzleHttp\json_decode($this->client->getResponse()->getContent());

        $generatedParts = explode('&X-Amz-Date=', $newResponse->pre_signed_url);
        $actualParts = explode('&X-Amz-Date=', (string)$presignedRequest->getUri());

        $this->assertEquals($actualParts[0], $generatedParts[0]);
    }

    public function testS3Settings() {

        $contentType = new ContentType();
        $field = new ContentTypeField();
        $field
            ->setIdentifier('file')
            ->setType('file');
        $contentType->addField($field);
        $fieldSettings = $field->getSettings();

        $service = $this->container->get('unite.cms.storage.service');


        // Test setting endpoint and bucket
        $fieldSettings->bucket = [
            'endpoint' => 'https://foo.com',
            'bucket' => 'baa',
            'key' => 'XXX',
            'secret' => 'XXX',
        ];
        $response = $service->createPreSignedUploadUrlForFieldPath('test.txt', $contentType, 'file');
        $this->assertStringStartsWith('https://foo.com/baa/' . $response->getUuid() . '/' . $response->getFilename(), $response->getPreSignedUrl());

        // Test setting path
        $fieldSettings->bucket['path'] = 'any/subpath';
        $response = $service->createPreSignedUploadUrlForFieldPath('test.txt', $contentType, 'file');
        $this->assertStringStartsWith('https://foo.com/baa/any/subpath/' . $response->getUuid() . '/' . $response->getFilename(), $response->getPreSignedUrl());

        // Test setting path
        $fieldSettings->bucket['path'] = '/any/subpath/';
        $response = $service->createPreSignedUploadUrlForFieldPath('test.txt', $contentType, 'file');
        $this->assertStringStartsWith('https://foo.com/baa/any/subpath/' . $response->getUuid() . '/' . $response->getFilename(), $response->getPreSignedUrl());

        // Test setting path
        $fieldSettings->bucket['path'] = '/';
        $response = $service->createPreSignedUploadUrlForFieldPath('test.txt', $contentType, 'file');
        $this->assertStringStartsWith('https://foo.com/baa/' . $response->getUuid() . '/' . $response->getFilename(), $response->getPreSignedUrl());

        $fieldSettings->bucket['region'] = 'any-region-12';
        $response = $service->createPreSignedUploadUrlForFieldPath('test.txt', $contentType, 'file');
        parse_str($response->getPreSignedUrl(), $params);
        $this->assertContains('/any-region-12', $params['X-Amz-Credential']);
    }
}
