<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Controller\GraphQLApiController;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Form\ContentDeleteFormType;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ApiFunctionalTestCase extends DatabaseAwareTestCase
{

    protected $data = [
        'foo_organization' => [
            '{
  "title": "Marketing & PR",
  "identifier": "marketing",
  "roles": [
    "ROLE_PUBLIC",
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "News",
      "identifier": "news",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Content",
          "identifier": "content",
          "type": "textarea",
          "settings": {}
        },
        {
          "title": "Category",
          "identifier": "category",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "content_type": "news_category"
          }
        },
        {
          "title": "Author",
          "identifier": "author",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "domain_member_type": "editor"
          }
        },
        {
          "title": "Not Empty",
          "identifier": "not_empty",
          "type": "text",
          "settings": {
            "not_empty": true
          }
        },
        {
          "title": "Number",
          "identifier": "number",
          "type": "integer"
        },
        {
          "title": "Float",
          "identifier": "float",
          "type": "number"
        },
        {
          "title": "Boolean",
          "identifier": "boolean",
          "type": "checkbox"
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    },
    {
      "title": "News Category",
      "identifier": "news_category",
      "fields": [
        {
          "title": "Name",
          "identifier": "name",
          "type": "text",
          "settings": {}
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    },
    {
      "title": "Lang test",
      "identifier": "lang",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        }
      ],
      "locales": ["de", "en", "fr"],
      "permissions": {
        "view content": "content.locale != \"fr\""
      }
    }
  ],
  "setting_types": [
    {
      "title": "Website",
      "identifier": "website",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Imprint",
          "identifier": "imprint",
          "type": "textarea",
          "settings": {}
        }
      ],
      "permissions": {
        "view setting": "true",
        "update setting": "member.type == \"editor\""
      },
      "locales": []
    },
    {
      "title": "Lang test",
      "identifier": "lang",
      "fields": [
        {
          "title": "Title",
          "identifier": "title",
          "type": "text",
          "settings": {}
        }
      ],
      "locales": ["de", "en", "fr"],
      "permissions": {
        "view setting": "content.locale != \"fr\"",
        "update setting": "true"
      }
    }
  ]
}',
'{
  "title": "Internal Content",
  "identifier": "intern",
  "roles": [
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "Time Tracking",
      "identifier": "time_tracking",
      "fields": [
        {
          "title": "Employee",
          "identifier": "employee",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Package",
          "identifier": "package",
          "type": "reference",
          "settings": {
            "domain": "intern",
            "content_type": "package"
          }
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    },
    {
      "title": "Working Packages",
      "identifier": "package",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    }
  ],
  "setting_types": []
}'
        ],
        'baa_organization' => [
            '{
  "title": "Marketing & PR",
  "identifier": "internal",
  "roles": [
    "ROLE_PUBLIC",
    "ROLE_EDITOR"
  ],
  "content_types": [
    {
      "title": "News",
      "identifier": "news",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Content",
          "identifier": "content",
          "type": "textarea",
          "settings": {}
        },
        {
          "title": "Category",
          "identifier": "category",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "content_type": "news_category"
          }
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    },
    {
      "title": "News Category",
      "identifier": "news_category",
      "fields": [
        {
          "title": "Name",
          "identifier": "name",
          "type": "text",
          "settings": {}
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    }
  ],
  "setting_types": [
    {
      "title": "Website",
      "identifier": "website",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Imprint",
          "identifier": "imprint",
          "type": "textarea",
          "settings": {}
        }
      ],
      "permissions": {
        "view setting": "true",
        "update setting": "true"
      },
      "locales": []
    }
  ]
}',
            '{
  "title": "Internal Content",
  "identifier": "intern",
  "content_types": [
    {
      "title": "Time Tracking",
      "identifier": "time_tracking",
      "fields": [
        {
          "title": "Employee",
          "identifier": "employee",
          "type": "text",
          "settings": {}
        },
        {
          "title": "Package",
          "identifier": "package",
          "type": "reference",
          "settings": {
            "domain": "intern",
            "content_type": "package"
          }
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    },
    {
      "title": "Working Packages",
      "identifier": "package",
      "fields": [
        {
          "title": "Title",
          "identifier": "title_title",
          "type": "text",
          "settings": {}
        }
      ],
      "views": [
        {
          "title": "All",
          "identifier": "all",
          "type": "table",
          "settings": {}
        }
      ],
      "locales": []
    }
  ],
  "setting_types": []
}'
        ],
    ];
    protected $member_types = ['editor', 'viewer'];

    /**
     * @var Domain[] $domains
     */
    protected $domains = [];

    /**
     * @var ApiKey[] $users
     */
    protected $users = [];

    /**
     * @var GraphQLApiController $controller
     */
    private $controller;

    public function setUp()
    {
        parent::setUp();

        // Create a full unite CMS structure with different organizations, domains and users.
        foreach($this->data as $id => $domains) {
            $org = new Organization();
            $org->setIdentifier($id)->setTitle(ucfirst($id));
            $this->em->persist($org);
            $this->em->flush($org);

            foreach($domains as $domain_data) {
                $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($domain_data);
                $domain->setOrganization($org);
                $this->domains[$domain->getIdentifier()] = $domain;

                // Allow editors to CRUD on viewer type and list / view on editor type.
                $domain->getDomainMemberTypes()->get('viewer')->setPermissions([
                    DomainMemberVoter::VIEW => 'true',
                    DomainMemberVoter::LIST => 'false',
                ]);

                $dmtField = new DomainMemberTypeField();
                $dmtField
                    ->setIdentifier('foo')
                    ->setTitle('foo')
                    ->setType('text');
                $domain->getDomainMemberTypes()->get('viewer')->addField($dmtField);

                $domain->getDomainMemberTypes()->get('editor')->setPermissions([
                    DomainMemberVoter::VIEW => 'member.type == "editor"',
                    DomainMemberVoter::LIST => 'member.type == "editor"',
                ]);

                $dmtField2 = new DomainMemberTypeField();
                $dmtField2
                    ->setIdentifier('foo')
                    ->setTitle('foo')
                    ->setType('text');
                $domain->getDomainMemberTypes()->get('editor')->addField($dmtField2);

                $this->em->persist($domain);
                $this->em->flush($domain);

                foreach($this->member_types as $mtype) {
                    $domainMember = new DomainMember();
                    $domainMember->setDomain($domain)->setDomainMemberType($domain->getDomainMemberTypes()->get($mtype));
                    $domainMember->setData(['foo' => 'baa']);
                    $this->users[$domain->getIdentifier() . '_' . $mtype] = new ApiKey();
                    $this->users[$domain->getIdentifier() . '_' . $mtype]->setName($domain->getIdentifier() . '_' . $mtype)->setOrganization($org);
                    $this->users[$domain->getIdentifier() . '_' . $mtype]->addDomain($domainMember);

                    $this->em->persist($this->users[$domain->getIdentifier() . '_' . $mtype]);
                    $this->em->flush($this->users[$domain->getIdentifier() . '_' . $mtype]);
                }

                // For each content type create some views and test content.
                foreach($domain->getContentTypes() as $ct) {

                    $other = new View();
                    $other->setTitle('Other')->setIdentifier('other')->setType('table');
                    $ct->addView($other);
                    $this->em->persist($other);
                    $this->em->flush($other);

                    for($i = 0; $i < 60; $i++) {
                        $content = new Content();
                        $content->setContentType($ct);

                        $content_data = [];

                        foreach($ct->getFields() as $field) {
                            switch ($field->getType()) {
                                case 'text': $content_data[$field->getIdentifier()] = $this->generateRandomMachineName(100); break;
                                case 'textarea': $content_data[$field->getIdentifier()] = '<p>' . $this->generateRandomMachineName(100) . '</p>'; break;
                                case 'integer': $content_data[$field->getIdentifier()] = $i; break;
                                case 'number': $content_data[$field->getIdentifier()] = $i / 3; break;
                                case 'checkbox': $content_data[$field->getIdentifier()] = ($i % 2 == 0); break;
                            }
                        }

                        $content->setData($content_data);
                        $this->em->persist($content);
                        $this->em->flush($content);
                    }

                    $this->em->refresh($ct);
                }

                $this->em->refresh($domain);
            }
        }

        $this->controller = new GraphQLApiController();
        $this->controller->setContainer(static::$container);
    }

    private function api(Domain $domain, UserInterface $user, string $query, array $variables = [], $set_csrf_token = FALSE, $firewall = 'api', $form_type = null) {

        // Fake a real HTTP request.
        $request = new Request([], [], [
            'organization' => $domain->getOrganization(),
            'domain' => $domain,
        ], [], [], [
            'REQUEST_METHOD' => 'POST',
        ], json_encode(['query' => $query, 'variables' => $variables]));


        // For each request, initialize the cms manager.
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [
            'organization' => IdentifierNormalizer::denormalize($domain->getOrganization()->getIdentifier()),
            'domain' => $domain->getIdentifier(),
        ]));

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        // If we fallback to the statefull main firewall, we need to add a csrf-token with the request.
        if($set_csrf_token) {
            $request->headers->set('X-CSRF-TOKEN', static::$container->get('security.csrf.token_manager')->getToken(StringUtil::fqcnToBlockPrefix(
                $form_type ?? FieldableFormType::class
            ))->getValue());
        }

        static::$container->get('security.token_storage')->setToken(new PostAuthenticationGuardToken($user, $firewall, []));

        $response = $this->controller->indexAction(
            $domain->getOrganization(),
            $domain,
            $request,
            static::$container->get('logger'),
            static::$container->get('unite.cms.graphql.schema_type_manager'),
            true
        );
        return json_decode($response->getContent());
    }

    private function assertApiResponse($expected, $actual) {

        if(!is_string($expected)) {
            $expected = json_encode($expected);
        }

        $this->assertEquals(json_decode($expected), $actual);
    }

    public function testAccessingAPI() {
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'page' => 1
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews {
                    page
                }
            }')
        );

        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'page' => 1
                ],
                'findNews_category' => [
                    'page' => 1
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews {
                    page
                },
                findNews_category {
                    page
                }
            }')
        );

        // Test accessing content / setting permissions
        $this->assertApiResponse([
            'data' => [
                'findNews' => [

                    '_permissions' => [
                        'LIST_CONTENT' => true,
                        'CREATE_CONTENT' => false,
                    ],

                    'result' => [
                        [
                            '_permissions' => [
                                'VIEW_CONTENT' => true,
                                'UPDATE_CONTENT' => false,
                                'DELETE_CONTENT' => false,
                            ],
                        ],
                    ],
                ],
                'WebsiteSetting' => [
                    '_permissions' => [
                        'VIEW_SETTING' => true,
                        'UPDATE_SETTING' => false,
                    ],
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews(limit:1) {
                
                    _permissions {
                        LIST_CONTENT,
                        CREATE_CONTENT
                    }
                   
                    result {
                        _permissions {
                            VIEW_CONTENT,
                            UPDATE_CONTENT,
                            DELETE_CONTENT
                        }
                    }
                },
                WebsiteSetting {
                    _permissions {
                        VIEW_SETTING,
                        UPDATE_SETTING
                    }
                }
            }')
        );

        // Test accessing content / setting permissions
        $this->assertApiResponse([
            'data' => [
                'findNews' => [

                    '_permissions' => [
                        'LIST_CONTENT' => true,
                        'CREATE_CONTENT' => true,
                    ],

                    'result' => [
                        [
                            '_permissions' => [
                                'VIEW_CONTENT' => true,
                                'UPDATE_CONTENT' => true,
                                'DELETE_CONTENT' => true,
                            ],
                        ],
                    ],
                ],
                'WebsiteSetting' => [
                    '_permissions' => [
                        'VIEW_SETTING' => true,
                        'UPDATE_SETTING' => true,
                    ],
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'],'query {
                findNews(limit:1) {
                
                    _permissions {
                        LIST_CONTENT,
                        CREATE_CONTENT
                    }
                   
                    result {
                        _permissions {
                            VIEW_CONTENT,
                            UPDATE_CONTENT,
                            DELETE_CONTENT
                        }
                    }
                },
                WebsiteSetting {
                    _permissions {
                        VIEW_SETTING,
                        UPDATE_SETTING
                    }
                }
            }')
        );
    }

    public function testSpecialOperations() {
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'total' => 0
                ],
            ],
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "title_title", operator: "IS NULL" }) {
                total
            }
        }'));
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'total' => 60
                ],
            ],
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "title_title", operator: "IS NOT NULL" }) {
                total
            }
        }'));
    }

    public function testGenericApiFindMethod() {
        $result = $this->api(
        $this->domains['marketing'],
        $this->users['marketing_viewer'],'query {
          find(limit: 500, types: ["news", "news_category"]) {
            total,
            result {
              id,
              type
              
              ... on NewsContent {
                title_title
              }
              
              ... on News_categoryContent {
                name
              }
            }
          }
        }');

        // Result should contain 60x news and other 41x news_category (default limit is 101)
        $count_news = 0;
        $count_category = 0;

        foreach($result->data->find->result as $content) {
            if($content->type == 'news') {
                $count_news++;
            }
            if($content->type == 'news_category') {
                $count_category++;
            }
        }

        $this->assertEquals(60, $count_news);
        $this->assertEquals(41, $count_category);
    }

    public function testAPIFiltering() {

        // First get all news
        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews(limit: 100) {
                    total,
                    result {
                        id,
                        title_title,
                        content
                    }
                }
            }');

        // Get title_title and content partial strings from any random content.
        $content1 = $news->data->findNews->result[rand(1, ($news->data->findNews->total / 2) - 1)];
        $content2 = $news->data->findNews->result[rand(($news->data->findNews->total / 2), $news->data->findNews->total - 1)];
        $content1_title_title_part = substr($content1->title_title, rand(1, 50), rand(1, 20));
        $content2_content_part = substr($content2->content, rand(1, 50), rand(1, 20));

        // Filter by exact title_title.
        $result = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query($value: String) {
                findNews(filter: { field: "title_title", operator: "=", value: $value }) {
                    total,
                    result {
                        id,
                        title_title,
                        content
                    }
                }
            }', [
                'value' => $content1->title_title
            ]
        );

        $this->assertGreaterThan(0, $result->data->findNews->total);
        $ids = [];
        foreach($result->data->findNews->result as $c) {
            $ids[] = $c->id;
        }
        $this->assertContains($content1->id, $ids);


        // Filter by exact title_title and exact content.
        $result = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query($title_title: String, $content: String) {
                findNews(filter: { AND: [
                    { field: "title_title", operator: "=", value: $title_title },
                    { field: "content", operator: "=", value: $content }
                ]}) {
                    total,
                    result {
                        id,
                        title_title,
                        content
                    }
                }
            }', [
                'title_title' => $content1->title_title,
                'content' => $content1->content,
            ]
        );

        $this->assertGreaterThan(0, $result->data->findNews->total);
        $ids = [];
        foreach($result->data->findNews->result as $c) {
            $ids[] = $c->id;
        }
        $this->assertContains($content1->id, $ids);


        // Filter by part title_title or part content.
        $result = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query($title_title: String, $content: String) {
                findNews(filter: { OR: [
                    { field: "title_title", operator: "LIKE", value: $title_title },
                    { field: "content", operator: "LIKE", value: $content }
                ]}) {
                    total,
                    result {
                        id,
                        title_title,
                        content
                    }
                }
            }', [
                'title_title' => '%' . $content1_title_title_part . '%',
                'content' => '%' . $content2_content_part . '%',
            ]
        );

        $this->assertGreaterThan(1, $result->data->findNews->total);
    }

    public function testAPISorting()
    {
        // Make two content have distinct created values
        $i = 1;
        $reflector = new \ReflectionProperty(Content::class, 'created');
        $reflector->setAccessible(true);
        foreach($this->domains['marketing']->getContentTypes()->first()->getContent() as $c) {
            $time = new \DateTime();
            $time->add(new \DateInterval('PT'.$i.'S'));
            $reflector->setValue($c, $time);

            if($i == 1) {
                $c->setData([
                    'title_title' => 'test_nested_sorting',
                    'content' => 'AAA',
                ]);
            }

            if($i == 2) {
                $c->setData([
                    'title_title' => 'test_nested_sorting',
                    'content' => 'ZZZ',
                ]);
            }

            $i++;
        }

        $this->em->flush();
        $this->em->refresh($this->domains['marketing']->getContentTypes()->first());
        $this->em->refresh($this->domains['marketing']);


        // First get all news
        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, sort: { field: "created", order: "ASC" }) {
                    total,
                    result {
                        created
                    }
                }
            }');

        $this->assertGreaterThan(0, $news->data->findNews->total);
        $this->assertTrue(($news->data->findNews->result[0]->created < $news->data->findNews->result[1]->created));

        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, sort: { field: "created", order: "DESC" }) {
                    total,
                    result {
                        created
                    }
                }
            }');

        $this->assertGreaterThan(0, $news->data->findNews->total);
        $this->assertTrue(($news->data->findNews->result[0]->created > $news->data->findNews->result[1]->created));

        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, 
                    filter: { field: "title_title", operator: "=", value: "test_nested_sorting" }, 
                    sort: { field: "content", order: "ASC" }) {
                    
                    total,
                    result {
                        content
                    }
                }
            }');

        $this->assertEquals(2, $news->data->findNews->total);
        $this->assertEquals('AAA', $news->data->findNews->result[0]->content);
        $this->assertEquals('ZZZ', $news->data->findNews->result[1]->content);

        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, 
                    filter: { field: "title_title", operator: "=", value: "test_nested_sorting" }, 
                    sort: { field: "content", order: "DESC" }) {
                    
                    total,
                    result {
                        content
                    }
                }
            }');

        $this->assertEquals(2, $news->data->findNews->total);
        $this->assertEquals('ZZZ', $news->data->findNews->result[0]->content);
        $this->assertEquals('AAA', $news->data->findNews->result[1]->content);

        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, 
                    filter: { field: "title_title", operator: "=", value: "test_nested_sorting" }, 
                    sort: [
                        { field: "title_title", order: "ASC" },
                        { field: "content", order: "ASC" }
                    ]) {
                    
                    total,
                    result {
                        content
                    }
                }
            }');

        $this->assertEquals(2, $news->data->findNews->total);
        $this->assertEquals('AAA', $news->data->findNews->result[0]->content);
        $this->assertEquals('ZZZ', $news->data->findNews->result[1]->content);

        $news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 2, 
                    filter: { field: "title_title", operator: "=", value: "test_nested_sorting" }, 
                    sort: [
                        { field: "title_title", order: "ASC" },
                        { field: "content", order: "DESC" }
                    ]) {
                    
                    total,
                    result {
                        content
                    }
                }
            }');

        $this->assertEquals(2, $news->data->findNews->total);
        $this->assertEquals('ZZZ', $news->data->findNews->result[0]->content);
        $this->assertEquals('AAA', $news->data->findNews->result[1]->content);
    }

    public function testFilteringWithCastFunction() {

        // Without any filters, we should be able to access all 60 content items.
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 60 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews { total }
        }'));

        // Half of the content should have a checked checkbox
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "boolean", operator: "=", value: "true", cast: CAST_BOOLEAN }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "boolean", operator: "=", value: "false", cast: CAST_BOOLEAN }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "boolean", operator: "=", value: "1", cast: CAST_BOOLEAN }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "boolean", operator: "=", value: "0", cast: CAST_BOOLEAN }) { total }
        }'));

        // Number is greater than 50. This should be 10 in total
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 9 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "number", operator: ">", value: "50", cast: CAST_INTEGER }) { total }
        }'));

        // Number is lower or equals 50. This should be 50 in total
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 51 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "number", operator: "<=", value: "50", cast: CAST_INTEGER }) { total }
        }'));

        // Number is lower 50. This should be 49 in total
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 50 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "number", operator: "<", value: "50", cast: CAST_INTEGER }) { total }
        }'));

        // Float is lower 10. This should be 30 in total
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "float", operator: "<", value: "10", cast: CAST_INTEGER }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 30 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "float", operator: "<", value: "10", cast: CAST_INTEGER }) { total }
        }'));


        // Now get the updated value of one of the content elements and update the content.
        $news = $this->domains['marketing']->getContentTypes()->get('news')->getContent()->get(0);
        $updated = $news->getUpdated()->getTimestamp() + 100;
        $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->update('UniteCMSCoreBundle:Content', 'c')
            ->set('c.updated', ':updated')
            ->where('c.id = :id')
            ->setParameter('id', $news->getId())
            ->setParameter('updated', date('Y-m-d H:i:s', $updated))
            ->getQuery()->execute();

        // Now we should have at least one element that has greater timestamp.
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 1 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "updated", operator: ">=", value: "' . $updated . '", cast: CAST_DATETIME }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 60 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "updated", operator: ">=", value: "' . $updated . '", cast: CAST_DATE }) { total }
        }'));

        // Now we should have at least one element that has greater timestamp.
        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 1 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "updated", operator: ">=", value: "' . date("Y-m-d H:i:s", $updated) . '" }) { total }
        }'));

        $this->assertApiResponse(['data' => [ 'findNews' => [ 'total' => 1 ]]], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
            findNews(filter: { field: "updated", operator: ">=", value: "' . date("Y-m-d H:i:s", $updated) . '", cast: CAST_DATETIME }) { total }
        }'));
    }

    public function testAccessReferencedValue() {

        $category = $this->domains['marketing']->getContentTypes()->get('news_category')->getContent()->get(0);
        $news = $this->domains['marketing']->getContentTypes()->get('news')->getContent()->get(0);

        $news->setData([
            'title_title' => 'with_category',
            'category' => [
                'domain' => 'marketing',
                'content_type' => 'news_category',
                'content' => $category->getId(),
            ],
            'author' => [
                'domain' => 'marketing',
                'content_type' => 'editor',
                'content' => $this->users['marketing_editor']->getDomainMembers($this->domains['marketing'])[0]->getId(),
            ],
        ]);

        $this->em->flush();
        $this->em->refresh($this->domains['marketing']->getContentTypes()->first());
        $this->em->refresh($this->domains['marketing']);

        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'total' => 1,
                    'result' => [
                        [
                            'id' => $news->getId(),
                            'category' => [
                                'id' => $category->getId(),
                                'name' => $category->getData()['name'],
                            ],
                            'author' => [
                                'id' => $this->users['marketing_editor']->getDomainMembers($this->domains['marketing'])[0]->getId(),
                                '_name' => 'marketing_editor',
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'query {
                findNews(limit: 1, filter: { field: "title_title", operator: "=", value: "with_category" }) {
                    total,
                    result {
                        id,
                        category {
                            id,
                            name
                        },
                        author {
                            id,
                            _name
                        }
                    }
                }
            }'));
    }

    public function testGetContentAndSetting() {

        $setting = $this->domains['marketing']->getSettingTypes()->first()->getSetting();
        $setting->setData([
            'title_title' => $this->generateRandomMachineName(100),
            'imprint' => $this->generateRandomMachineName(100)
        ]);

        $this->em->persist($setting);
        $this->em->flush();
        $this->em->refresh($this->domains['marketing']->getSettingTypes()->first());
        $this->em->refresh($this->domains['marketing']);
        $content = $this->domains['marketing']->getContentTypes()->first()->getContent()->get(0);

        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query($newsID: ID!) {
                getNews(id: $newsID) {
                    id,
                    title_title,
                    content
                },
                WebsiteSetting {
                    title_title,
                    imprint
                }
            }', [
            'newsID' => $content->getId(),
        ]);

        $this->assertApiResponse([
            'data' => [
                'getNews' => [
                    'id' => $content->getId(),
                    'title_title' => $content->getData()['title_title'],
                    'content' => $content->getData()['content']
                ],
                'WebsiteSetting' => [
                    'title_title' => $setting->getData()['title_title'],
                    'imprint' => $setting->getData()['imprint']
                ],
            ],
        ], $response);
    }

    public function testContentPagination() {

        // Get all News ids.
        $ids = [];
        $all_news = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 100) {
                    total,
                    result {
                        id
                    }
                }
            }');

        foreach($all_news->data->findNews->result as $content) {
            $ids[] = $content->id;
        }

        // Test pagination with limit 0 and page 0.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(limit: 0, page: 1) { total, result { id } }
            }');
        $this->assertNull($response->data->findNews);
        $this->assertGreaterThan(0, count($response->errors));

        // Test pagination with too big offset.
        $this->assertApiResponse([
            'data' => [ 'findNews' => [ 'total' => 60, 'result' => [] ] ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'query {
                findNews(page: 1000) { total, result { id } }
            }'));

        // Test pagination with negative page should be the same as with page 1.
        $this->assertEquals(

            $this->api(
                $this->domains['marketing'],
                $this->users['marketing_viewer'], 'query {
                    findNews(page: 1) { total, result { id } }
                }'),
            $this->api(
                $this->domains['marketing'],
                $this->users['marketing_viewer'], 'query {
                    findNews(page: -5) { total, result { id } }
                }')
        );



        // Test pagination with random limit of 1 .. 1/4 of total.
        $page_size = rand(10, 15);
        $page = 1;
        while ($page * $page_size < $all_news->data->findNews->total) {
            $page_ids = [];
            $response = $this->api(
                $this->domains['marketing'],
                $this->users['marketing_viewer'], 'query($page: Int, $limit: Int) {
                    findNews(page: $page, limit: $limit) { total, result { id } }
                }', ['page' => $page, 'limit' => $page_size]);

            foreach($response->data->findNews->result as $content) {
                $page_ids[] = $content->id;
            }

            $this->assertEquals(array_slice($ids, ($page - 1) * $page_size, $page_size), $page_ids);
            $page++;
        }

    }

    public function testAPICRUDMethod() {

        // Try to create content without permissions.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'mutation {
                createNews_category(data: { name: "First Category" }, persist: true) {
                    id, 
                    name
                }
            }');

        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to create content in content type 'News Category'.", $response->errors[0]->message);

        // Try to create content with permissions but do not persist.
        $initialCount = $this->em->getRepository('UniteCMSCoreBundle:Content')->count([]);
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation {
                createNews_category(data: { name: "First Category" }, persist: false) {
                    id, 
                    name
                }
            }');

        $this->assertEquals($initialCount, $this->em->getRepository('UniteCMSCoreBundle:Content')->count([]));
        $this->assertTrue(empty($response->errors));
        $category = $response->data->createNews_category;
        $this->assertEmpty($category->id);
        $this->assertEquals('First Category', $category->name);

        // Try to create content with permissions.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation {
                createNews_category(data: { name: "First Category" }, persist: true) {
                    id, 
                    name
                }
            }');

        $this->assertEquals($initialCount+1, $this->em->getRepository('UniteCMSCoreBundle:Content')->count([]));
        $this->assertTrue(empty($response->errors));

        $category = $response->data->createNews_category;
        $this->assertEquals('First Category', $category->name);
        $this->assertNotEmpty($category->id);

        // Now create a news content with invalid content.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($category: ReferenceFieldTypeInput) {
                createNews(data: { title_title: "First News", content: "<p>Hello World</p>", category: $category, not_empty: "" }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
                'category' => [
                    'domain' => 'marketing',
                    'content_type' => 'news_category',
                    'content' => 'foo',
                ]
            ]);

        $this->assertNotEmpty($response->errors);
        $this->assertEquals(static::$container->get('translator')->trans('not_blank', [], 'validators'), $response->errors[0]->message);
        $this->assertEquals(['createNews', 'data', 'not_empty'], $response->errors[0]->path);

        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($category: ReferenceFieldTypeInput) {
                createNews(data: { title_title: "First News", content: "<p>Hello World</p>", category: $category, not_empty: "Foo" }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
            'category' => [
                'domain' => 'marketing',
                'content_type' => 'news_category',
                'content' => 'foo',
            ]
        ]);

        $this->assertNotEmpty($response->errors);
        $this->assertEquals(static::$container->get('translator')->trans('invalid_reference_definition', [], 'validators'), $response->errors[0]->message);
        $this->assertEquals(['createNews', 'data', 'category'], $response->errors[0]->path);

        // Now create a news content with valid content.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($category: ReferenceFieldTypeInput) {
                createNews(data: { title_title: "First News", content: "<p>Hello World</p>", category: $category, not_empty: "FOO" }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
            'category' => [
                'domain' => 'marketing',
                'content_type' => 'news_category',
                'content' => $category->id,
            ]
        ]);

        $news = $response->data->createNews;
        $this->assertTrue(empty($response->errors));
        $this->assertNotEmpty($news->id);
        $this->assertNotEmpty($news->title_title);
        $this->assertNotEmpty($news->content);
        $this->assertNotEmpty($news->category);
        $this->assertEquals($category, $news->category);

        // Update the category, but with wrong user.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'mutation($id: ID!) {
                updateNews_category(id: $id, data: { name: "Updated Category Title" }, persist: true) {
                    id, 
                    name
                }
            }', ['id' => $category->id]);

        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to update content with id '" . $category->id . "'.", $response->errors[0]->message);

        // Update the category with right user.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                updateNews_category(id: $id, data: { name: "Updated Category Title" }, persist: true) {
                    id, 
                    name
                }
            }', ['id' => $category->id]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals((object)[
            'id' => $category->id,
            'name' => 'Updated Category Title',
        ], $response->data->updateNews_category);

        // Update a news content with invalid content.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!, $category: ReferenceFieldTypeInput) {
                updateNews(id: $id, data: { title_title: "Updated News", content: "<p>Hello new World</p>", category: $category }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
            'id' => $news->id,
            'category' => [
                'domain' => 'marketing',
                'content_type' => 'news_category',
                'content' => 'foo',
            ]
        ]);

        $this->assertNotEmpty($response->errors);
        $this->assertContains(static::$container->get('translator')->trans('invalid_reference_definition', [], 'validators'), $response->errors[0]->message);
        $this->assertEquals(['updateNews', 'data', 'category'], $response->errors[0]->path);

        // update a news content with valid content.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!, $category: ReferenceFieldTypeInput) {
                updateNews(id: $id, data: { title_title: "Updated News", content: "<p>Hello new World</p>", category: $category }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
            'id' => $news->id,
            'category' => [
                'content' => null,
            ]
        ]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->updateNews->id);
        $this->assertEquals("Updated News", $response->data->updateNews->title_title);
        $this->assertEquals("<p>Hello new World</p>", $response->data->updateNews->content);
        $this->assertNull($response->data->updateNews->category);

        // update partial news content with valid content.
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                updateNews(id: $id, data: { title_title: "Updated News2" }, persist: true) {
                    id, 
                    title_title,
                    content,
                    category {
                      id,
                      name
                    }
                }
            }', [
            'id' => $news->id,
            'category' => [
                'content' => null,
            ]
        ]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->updateNews->id);
        $this->assertEquals("Updated News2", $response->data->updateNews->title_title);
        $this->assertEquals("<p>Hello new World</p>", $response->data->updateNews->content);
        $this->assertNull($response->data->updateNews->category);

        // delete content without permission
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: false) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id,
            'category' => null
        ]);

        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to delete content with id '" . $news->id . "'.", $response->errors[0]->message);

        $originalCountNews = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query { findNews { total } }')->data->findNews->total;

        // delete content with permission, but without persist = true
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: false) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id,
            'category' => null
        ]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->deleteNews->id);
        $this->assertFalse($response->data->deleteNews->deleted);
        $this->assertEquals($originalCountNews, $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query { findNews { total } }')->data->findNews->total);

        // delete content with permission, with persist = true
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id,
            'category' => null
        ]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->deleteNews->id);
        $this->assertTrue($response->data->deleteNews->deleted);
        $this->assertEquals($originalCountNews -1, $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query { findNews { total } }')->data->findNews->total);

        // Now try to get the deleted content without permission.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query($id: String) {
                findNews(deleted: true, filter: { field: "id", value: $id, operator: "=" }) {
                    result {
                        id,
                        deleted
                    }
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEmpty($response->data->findNews->result);

        // Now try to get the deleted content with permission.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query($id: String) {
                findNews(deleted: true, filter: { field: "id", value: $id, operator: "=" }) {
                    result {
                        id,
                        deleted
                    }
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->findNews->result[0]->id);
        $this->assertNotNull($response->data->findNews->result[0]->deleted);

        // Now try to recover the deleted content without permission.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'mutation($id: ID!) {
                recoverNews(id: $id, persist: true) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to recover this content.", $response->errors[0]->message);

        // Now try to recover the deleted content with permission but not persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                recoverNews(id: $id, persist: false) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->recoverNews->id);
        $this->assertNotNull($response->data->recoverNews->deleted);

        // Now try to recover the deleted content with permission and persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                recoverNews(id: $id, persist: true) {
                    id,
                    deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->recoverNews->id);
        $this->assertNull($response->data->recoverNews->deleted);






        // Now try to delete definitely the non-deleted content without permission.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true, definitely: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertNotEmpty($response->errors);
        $this->assertEquals("Content was not found.", $response->errors[0]->message);

        // Now try to delete definitely the deleted content with permission but not persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: false, definitely: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertNotEmpty($response->errors);
        $this->assertEquals("Content was not found.", $response->errors[0]->message);





        // Delete news.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id,
            'category' => null
        ]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->deleteNews->id);
        $this->assertTrue($response->data->deleteNews->deleted);


        // Now try to delete definitely the deleted content without permission.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true, definitely: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to delete content with id '$news->id'.", $response->errors[0]->message);

        // Now try to delete definitely the deleted content with permission but not persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: false, definitely: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->deleteNews->id);
        $this->assertTrue($response->data->deleteNews->deleted);
        $this->assertFalse($response->data->deleteNews->definitely_deleted);

        // Now try to recover the deleted content with permission and persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true, definitely: true) {
                    id,
                    deleted,
                    definitely_deleted
                }
            }', [
            'id' => $news->id
        ]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals($news->id, $response->data->deleteNews->id);
        $this->assertFalse($response->data->deleteNews->deleted);
        $this->assertTrue($response->data->deleteNews->definitely_deleted);
    }

    public function testAPICRUDForCTWithLang() {

        // Test that locale should be required for create.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation {
            createLang(data: { title: "With language" }, persist: true) {
              id,
              title
            }
        }');

        $this->assertNotEmpty($response->errors);
        $this->assertEquals('Field "createLang" argument "locale" of type "String!" is required but not provided.', $response->errors[0]->message);

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation {
            createLang(data: { title: "With language" }, locale: "de", persist: true) {
              id,
              title,
              locale
            }
        }');

        $this->assertTrue(empty($response->errors));
        $this->assertNotNull($response->data->createLang->id);
        $this->assertEquals('With language', $response->data->createLang->title);
        $this->assertEquals('de', $response->data->createLang->locale);

        $id = $response->data->createLang->id;

        // Test that locale should not be required for update but can be set.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
            updateLang(id: $id, data: { title: "Updated title" }, persist: true) {
              id,
              title,
              locale
            }
        }', ['id' => $id]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals('Updated title', $response->data->updateLang->title);
        $this->assertEquals('de', $response->data->updateLang->locale);

        // Test that locale should not be required for update but can be set.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
            updateLang(id: $id, data: {}, locale: "en", persist: true) {
              id,
              title,
              locale
            }
        }', ['id' => $id]);

        $this->assertTrue(empty($response->errors));
        $this->assertEquals('Updated title', $response->data->updateLang->title);
        $this->assertEquals('en', $response->data->updateLang->locale);

        // Find content by locale
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query {
            findLang(filter: { field: "locale", operator: "=", value: "foo"}) {
                total
            }
        }');
        $this->assertEquals(0, $response->data->findLang->total);

        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query {
            findLang(filter: { field: "locale", operator: "=", value: "en"}) {
                total
            }
        }');
        $this->assertGreaterThanOrEqual(1, $response->data->findLang->total);

        // Add a translation for english content.
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($id);
        $trans = new Content();
        $trans->setContentType($content->getContentType());
        $trans->setData(['title' => 'DE content']);
        $trans->setLocale('de');
        $content->addTranslation($trans);

        $this->em->persist($trans);
        $this->em->flush();

        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query($id: ID!) {
            getLang(id: $id) {
                id,
                locale,
                title,
                translations(locales: ["de", "en"]) {
                    id, 
                    locale,
                    title,
                    translations(locales: "en") {
                        id,
                        locale,
                        title
                    }
                }
            }
        }', ['id' => $id]);

        $this->assertTrue(empty($response->errors));

        // Make sure, that only de translation is filled out.
        $this->assertEquals('en', $response->data->getLang->locale);
        $this->assertCount(1, $response->data->getLang->translations);
        $this->assertEquals('DE content', $response->data->getLang->translations[0]->title);
        $this->assertEquals('de', $response->data->getLang->translations[0]->locale);

        // Make sure, that also translations can access their translationOf
        $this->assertCount(1, $response->data->getLang->translations[0]->translations);
        $this->assertEquals($id, $response->data->getLang->translations[0]->translations[0]->id);
        $this->assertEquals('en', $response->data->getLang->translations[0]->translations[0]->locale);
        $this->assertEquals('Updated title', $response->data->getLang->translations[0]->translations[0]->title);

        $fr_trans = new Content();
        $fr_trans->setContentType($content->getContentType());
        $fr_trans->setLocale('fr');
        $content->addTranslation($fr_trans);

        $this->em->persist($fr_trans);
        $this->em->flush();

        // Try to access translation, the user do not have access to.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query($id: ID!) {
            getLang(id: $id) {
                translations(locales: "fr") {
                    id, 
                    locale,
                    title
                }
            }
        }', ['id' => $content->getId()]);

        $this->assertTrue(empty($response->data->getLang->translations));
    }

    public function testAPICRUDForSTWithLang() {

        $setting = new Setting();
        $setting->setSettingType($this->domains['marketing']->getSettingTypes()->get('lang'));
        $setting->setLocale('en');
        $setting->setData(['title' => 'Updated title']);
        $this->em->persist($setting);

        $trans = new Setting();
        $trans->setSettingType($this->domains['marketing']->getSettingTypes()->get('lang'));
        $trans->setLocale('de');
        $trans->setData(['title' => 'DE title']);
        $this->em->persist($trans);

        $fr_trans = new Setting();
        $fr_trans->setSettingType($this->domains['marketing']->getSettingTypes()->get('lang'));
        $fr_trans->setLocale('fr');
        $fr_trans->setData(['title' => 'FR title, no access']);
        $this->em->persist($fr_trans);
        $this->em->flush();

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            LangSetting {
                locale,
                title,
                translations {
                    locale,
                    title
                }
            }
        }');

        $this->assertTrue(empty($response->errors));

        // Make sure, that only de and en translations are present and not fr translation, we do not have access to.
        $this->assertEquals('en', $response->data->LangSetting->locale);
        $this->assertEquals('Updated title', $response->data->LangSetting->title);

        $this->assertCount(2, $response->data->LangSetting->translations);
        $this->assertEquals('DE title', $response->data->LangSetting->translations[0]->title);
        $this->assertEquals('de', $response->data->LangSetting->translations[0]->locale);
        $this->assertEquals('Updated title', $response->data->LangSetting->translations[1]->title);
        $this->assertEquals('en', $response->data->LangSetting->translations[1]->locale);
    }

    public function testAPICRUDActionsWithCookieAuthentication() {

        // The api can also be accessed via the main firewall, with uses cookie authentication. If this is the case,
        // we also need to provide a CSRF token with the request.

        // Try READ without csrf token
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'page' => 1
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews {
                    page
                }
            }', [], false, 'main')
        );

        // Try READ with csrf token
        $this->assertApiResponse([
            'data' => [
                'findNews' => [
                    'page' => 1
                ]
            ]
        ], $this->api(
            $this->domains['marketing'],
            $this->users['marketing_viewer'],'query {
                findNews {
                    page
                }
            }', [], true, 'main')
        );

        // Try Create without csrf token
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation {
                createNews(data: { title_title: "First News", not_empty: "Foo" }, persist: true) {
                    id, 
                    title_title
                }
            }', [], false, 'main');

        $this->assertNotEmpty($response->errors);
        $this->assertEquals('The CSRF token is invalid. Please try to resubmit the form.', $response->errors[0]->message);
        $this->assertEquals(['createNews'], $response->errors[0]->path);

        // Try Create with csrf token
        $response = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation {
                createNews(data: { title_title: "First News", not_empty: "Foo" }, persist: true) {
                    id, 
                    title_title
                }
            }', [], true, 'main');

        $this->assertNotNull($response->data->createNews->id);
        $this->assertEquals('First News', $response->data->createNews->title_title);

        // Try Update
        $responseUpdate = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                updateNews(id: $id, data: { title_title: "Updated News" }, persist: true) { 
                    title_title
                }
            }', [
                'id' => $response->data->createNews->id,
        ], false, 'main');

        $this->assertNotEmpty($responseUpdate->errors);
        $this->assertStringStartsWith("The CSRF token is invalid. Please try to resubmit the form.", $responseUpdate->errors[0]->message);
        $this->assertEquals(['updateNews'], $responseUpdate->errors[0]->path);

        $responseUpdate = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                updateNews(id: $id, data: { title_title: "Updated News" }, persist: true) { 
                    title_title
                }
            }', [
            'id' => $response->data->createNews->id,
        ], true, 'main');

        $this->assertEquals('Updated News', $responseUpdate->data->updateNews->title_title);

        // Try Delete
        $responseDelete = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true) { 
                    id
                }
            }', [
            'id' => $response->data->createNews->id,
        ], false, 'main', ContentDeleteFormType::class);

        $this->assertNotEmpty($responseDelete->errors);
        $this->assertStringStartsWith("The CSRF token is invalid. Please try to resubmit the form.", $responseDelete->errors[0]->message);
        $this->assertEquals(['deleteNews'], $responseDelete->errors[0]->path);

        $responseDelete = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_editor'], 'mutation($id: ID!) {
                deleteNews(id: $id, persist: true) { 
                    deleted
                }
            }', [
            'id' => $response->data->createNews->id,
        ], true, 'main', ContentDeleteFormType::class);
        $this->assertEquals(true, $responseDelete->data->deleteNews->deleted);

    }

    public function testFindForContentWithoutPermission() {

        // On lang CT, view is only allowed if lang != fr.
        foreach($this->em->getRepository('UniteCMSCoreBundle:Content')->findAll() as $content) {
            $this->em->remove($content);
        }

        $content1 = new Content();
        $content1->setLocale('de')->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));

        $content2 = new Content();
        $content2->setLocale('fr')->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));

        $this->em->persist($content1);
        $this->em->persist($content2);
        $this->em->flush();

        // Find content should only return one content (de)
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query {
            findLang {
                total,
                result {
                    locale
                }
            }
        }');
        $this->assertCount(1, $response->data->findLang->result);

        // Total gets the total number of all items.
        $this->assertEquals(1, $response->data->findLang->total);

        // Find content should only return one content (de)
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query {
            findLang {
                total,
                result {
                    locale
                }
            }
        }', [], false, 'main');
        $this->assertCount(1, $response->data->findLang->result);
        $this->assertEquals(1, $response->data->findLang->total);
    }

    public function testFindDomainMemberForEditor() {

        // Editors can be listed and viewed by editors.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findEditorMember {
                total,
                result {
                    id,
                    type,
                    created,
                    updated,
                    foo,
                    _name
                }
            }
        }');
        $this->assertFalse(isset($response->errors));
        $this->assertEquals(1, $response->data->findEditorMember->total);
        $this->assertEquals($this->users['marketing_editor']->getDomainMembers($this->domains['marketing'])[0]->getId(), $response->data->findEditorMember->result[0]->id);
        $this->assertEquals('editor', $response->data->findEditorMember->result[0]->type);
        $this->assertEquals('baa', $response->data->findEditorMember->result[0]->foo);
        $this->assertEquals('marketing_editor', $response->data->findEditorMember->result[0]->_name);

        // Editors can get a single editor.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query($id: ID!) {
            getEditorMember(id: $id) {
                id,
                type,
                created,
                updated,
                foo,
                _name
            }
        }', ['id' => $this->users['marketing_editor']->getDomainMembers($this->domains['marketing'])[0]->getId()]);
        $this->assertFalse(isset($response->errors));
        $this->assertEquals($this->users['marketing_editor']->getDomainMembers($this->domains['marketing'])[0]->getId(), $response->data->getEditorMember->id);
        $this->assertEquals('editor', $response->data->getEditorMember->type);
        $this->assertEquals('baa', $response->data->getEditorMember->foo);
        $this->assertEquals('marketing_editor', $response->data->getEditorMember->_name);

        // Editors cannot access view member at all
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findViewerMember {
                total,
                result {
                    id,
                    type,
                    created,
                    updated,
                    foo
                }
            }
        }');

        $this->assertNotNull($response->errors);
        $this->assertStringStartsWith('Cannot query field "findViewerMember" on type "Query".', $response->errors[0]->message);
        $this->assertFalse(isset($response->data));

        // If a user cannot list fieldable content, he_she also cannot get one.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            getViewerMember(id: 2) {
                id,
                type,
                created,
                updated,
                foo,
                _name
            }
        }');
        $this->assertNotNull($response->errors);
        $this->assertStringStartsWith('Cannot query field "getViewerMember" on type "Query".', $response->errors[0]->message);
        $this->assertFalse(isset($response->data));
    }

    public function testFilterAndOrderDomainMember() {

        for($i = 1; $i <= 5; $i++) {
            $api_key = new ApiKey();
            $api_key->setName('API Key '.$i.' X')->setToken('XXX'.$i);
            $domainMember1 = new DomainMember();
            $domainMember1->setDomain($this->domains['marketing'])->setAccessor($api_key)->setDomainMemberType($this->domains['marketing']->getDomainMemberTypes()->get('editor'));

            $user = new User();
            $user->setName('Editor ' . $i)->setEmail('test_filter_api_func_test'.$i.'@example.com')->setPassword('XXX');
            $domainMember2 = new DomainMember();
            $domainMember2->setDomain($this->domains['marketing'])->setAccessor($user)->setDomainMemberType($this->domains['marketing']->getDomainMemberTypes()->get('editor'));

            $this->em->persist($api_key);
            $this->em->persist($user);
            $this->em->persist($domainMember1);
            $this->em->persist($domainMember2);
        }

        $this->em->flush();

        // Viewers cannot access editors at all.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query($sort: [SortInput], $filter: FilterInput) {
            findEditorMember(filter: $filter, sort: $sort) {
                total,
                result {
                    id,
                    _name,
                    type,
                    created,
                    updated,
                    foo
                }
            }
        }', [
            'sort' => [
                [ 'field' => '_name', 'order' => 'DESC' ],
                [ 'field' => '_name', 'order' => 'DESC' ],
                [ 'field' => 'id', 'order' => 'DESC' ]
            ],
            'filter' => [
                'AND' => [
                    [ 'field' => 'id', 'operator' => 'IS NOT NULL', 'value' => '' ],
                    [ 'field' => '_name', 'operator' => 'IS NOT NULL', 'value' => '' ],
                    [
                        'OR' => [
                            [ 'field' => '_name', 'operator' => 'LIKE', 'value' => 'E%' ],
                            [ 'field' => '_name', 'operator' => 'LIKE', 'value' => '%X' ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertFalse(isset($response->errors));
        $this->assertEquals(10, $response->data->findEditorMember->total);
        $this->assertEquals('Editor 5', $response->data->findEditorMember->result[0]->_name);
        $this->assertEquals('Editor 4', $response->data->findEditorMember->result[1]->_name);
        $this->assertEquals('Editor 3', $response->data->findEditorMember->result[2]->_name);
        $this->assertEquals('Editor 2', $response->data->findEditorMember->result[3]->_name);
        $this->assertEquals('Editor 1', $response->data->findEditorMember->result[4]->_name);
        $this->assertEquals('API Key 5 X', $response->data->findEditorMember->result[5]->_name);
        $this->assertEquals('API Key 4 X', $response->data->findEditorMember->result[6]->_name);
        $this->assertEquals('API Key 3 X', $response->data->findEditorMember->result[7]->_name);
        $this->assertEquals('API Key 2 X', $response->data->findEditorMember->result[8]->_name);
        $this->assertEquals('API Key 1 X', $response->data->findEditorMember->result[9]->_name);
    }

    public function testFindDomainMemberForViewer() {

        // Viewers cannot access editors at all.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'query {
            findEditorMember {
                total,
                result {
                    id,
                    type,
                    created,
                    updated,
                    foo
                }
            }
        }');

        $this->assertNotNull($response->errors);
        $this->assertStringStartsWith('Cannot query field "findEditorMember" on type "Query".', $response->errors[0]->message);
        $this->assertFalse(isset($response->data));
    }

    public function testIgnoreCaseSortFlag() {

        foreach($this->em->getRepository(Content::class)->findAll() as $content) {
            $this->em->remove($content);
        }

        $c1 = new Content();
        $c1->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));
        $c1->setData(['title' => 'BBB']);
        $c2 = new Content();
        $c2->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));
        $c2->setData(['title' => 'bbbb']);
        $c3 = new Content();
        $c3->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));
        $c3->setData(['title' => 'aaa']);
        $c4 = new Content();
        $c4->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));
        $c4->setData(['title' => 'baba']);
        $c5 = new Content();
        $c5->setContentType($this->domains['marketing']->getContentTypes()->get('lang'));
        $c5->setData(['title' => 'AbAb']);

        $this->em->persist($c1);
        $this->em->persist($c2);
        $this->em->persist($c3);
        $this->em->persist($c4);
        $this->em->persist($c5);
        $this->em->flush();

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "ASC" }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['AbAb', 'BBB', 'aaa', 'baba', 'bbbb'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "DESC" }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['bbbb', 'baba', 'aaa', 'BBB', 'AbAb'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "ASC", ignore_case: false }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['AbAb', 'BBB', 'aaa', 'baba', 'bbbb'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "DESC", ignore_case: false }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['bbbb', 'baba', 'aaa', 'BBB', 'AbAb'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "ASC", ignore_case: true }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['aaa', 'AbAb', 'baba', 'BBB', 'bbbb'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));

        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'query {
            findLang(sort: { field: "title", order: "DESC", ignore_case: true }) {
                result {
                    title
                }
            }
        }');
        $this->assertEquals(['bbbb', 'BBB', 'baba', 'AbAb', 'aaa'], array_map(function($r){ return $r->title; }, $response->data->findLang->result));
    }

    public function testGetRevisions() {

        $setting = $this->domains['marketing']->getSettingTypes()->get('website')->getSetting();
        $this->em->persist($setting);
        $this->em->flush($setting);

        $this->domains['marketing']->getDomainMemberTypes()->get('editor')->setPermissions([
            DomainMemberVoter::VIEW => 'true',
            DomainMemberVoter::LIST => 'true',
            DomainMemberVoter::UPDATE => 'member.type == "editor"',
        ]);
        $this->em->flush($this->domains['marketing']->getDomainMemberTypes()->get('editor'));

        $queries = [
            'query { findEditorMember(limit: 1) { result { _revisions { version, author, date, action, content { id, type } } } } }',
            'query { findNews(limit: 1) { result { _revisions { version, author, date, action, content { id, type } } } } }',
            'query { WebsiteSetting { _revisions { version, author, date, action, content { id, type } } } }',
        ];

        // Try as viewer, should not be allowed.
        foreach($queries as $delta => $query) {
            $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], $query);
            $this->assertTrue(empty($response->errors));
            switch ($delta) {
                case 0: $this->assertEmpty($response->data->findEditorMember->result[0]->_revisions); break;
                case 1: $this->assertEmpty($response->data->findNews->result[0]->_revisions); break;
                case 2: $this->assertEmpty($response->data->WebsiteSetting->_revisions); break;
            }
        }

        // Try as editor, should be allowed.
        foreach($queries as $delta => $query) {
            $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], $query);
            $this->assertTrue(empty($response->errors));
            switch ($delta) {
                case 0:
                    $this->assertEquals('1', $response->data->findEditorMember->result[0]->_revisions[0]->version);
                    $this->assertNotNull($response->data->findEditorMember->result[0]->_revisions[0]->date);
                    $this->assertNotNull($response->data->findEditorMember->result[0]->_revisions[0]->content->id);
                    $this->assertEquals('editor', $response->data->findEditorMember->result[0]->_revisions[0]->content->type);
                break;
                case 1:
                    $this->assertEquals('1', $response->data->findNews->result[0]->_revisions[0]->version);
                    $this->assertNotNull($response->data->findNews->result[0]->_revisions[0]->date);
                    $this->assertNotNull($response->data->findNews->result[0]->_revisions[0]->content->id);
                    $this->assertEquals('news', $response->data->findNews->result[0]->_revisions[0]->content->type);
                break;
                case 2:
                    $this->assertEquals('1', $response->data->WebsiteSetting->_revisions[0]->version);
                    $this->assertNotNull($response->data->WebsiteSetting->_revisions[0]->date);
                    $this->assertNotNull($response->data->WebsiteSetting->_revisions[0]->content->id);
                    $this->assertEquals('website', $response->data->WebsiteSetting->_revisions[0]->content->type);
                break;
            }
        }
    }

    public function testRevertMutations() {

        $contentType = $this->domains['marketing']->getContentTypes()->get('news');
        $content = $this->em->getRepository(Content::class)->findOneBy(['contentType' => $contentType]);
        $originalName = $content->getData()['title_title'];
        $content->setData(['title_title' => 'updated']);
        $this->em->flush($content);

        // Try to revert content to version 1 without permissions.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_viewer'], 'mutation($id: ID!) {
            revertNews(id: $id, version: 1, persist: true) {
                id, 
                title_title
            }
        }', ['id' => $content->getId()]);
        $this->assertNotEmpty($response->errors);
        $this->assertEquals("You are not allowed to revert this content.", $response->errors[0]->message);

        // Try to revert content to version 1 with but without persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
            revertNews(id: $id, version: 1, persist: false) {
                id, 
                title_title
            }
        }', ['id' => $content->getId()]);
        $this->assertTrue(empty($response->errors));
        $this->em->refresh($content);
        $this->assertEquals('updated', $response->data->revertNews->title_title);
        $this->assertEquals('updated', $content->getData()['title_title']);

        // Try to revert content to version 1 with but without persist.
        $response = $this->api($this->domains['marketing'], $this->users['marketing_editor'], 'mutation($id: ID!) {
            revertNews(id: $id, version: 1, persist: true) {
                id, 
                title_title
            }
        }', ['id' => $content->getId()]);
        $this->assertTrue(empty($response->errors));
        $this->em->refresh($content);
        $this->assertEquals($originalName, $response->data->revertNews->title_title);
        $this->assertEquals($originalName, $content->getData()['title_title']);
    }
}
