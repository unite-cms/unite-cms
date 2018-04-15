<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Controller\GraphQLApiController;
use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ApiMaximumNestingLevelTest extends DatabaseAwareTestCase
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
          "identifier": "title",
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
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
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
        },
        {
          "title": "News",
          "identifier": "news",
          "type": "reference",
          "settings": {
            "domain": "marketing",
            "content_type": "news"
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
      "permissions": {
        "view content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "list content": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "create content": [
          "ROLE_EDITOR"
        ],
        "update content": [
          "ROLE_EDITOR"
        ],
        "delete content": [
          "ROLE_EDITOR"
        ]
      },
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
          "identifier": "title",
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
        "view setting": [
          "ROLE_PUBLIC",
          "ROLE_EDITOR"
        ],
        "update setting": [
          "ROLE_EDITOR"
        ]
      },
      "locales": []
    }
  ]
}',
        ],
    ];

    protected $roles = ['ROLE_PUBLIC', 'ROLE_EDITOR'];

    /**
     * @var Domain[] $domains
     */
    protected $domains = [];

    /**
     * @var ApiClient[] $users
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
        foreach ($this->data as $id => $domains) {
            $org = new Organization();
            $org->setIdentifier($id)->setTitle(ucfirst($id));
            $this->em->persist($org);
            $this->em->flush($org);

            foreach ($domains as $domain_data) {
                $domain = $this->container->get('unite.cms.domain_definition_parser')->parse($domain_data);
                $domain->setOrganization($org);
                $this->domains[$domain->getIdentifier()] = $domain;
                $this->em->persist($domain);
                $this->em->flush($domain);

                foreach ($this->roles as $role) {
                    $this->users[$domain->getIdentifier().'_'.$role] = new ApiClient();
                    $this->users[$domain->getIdentifier().'_'.$role]->setName(ucfirst($role))->setRoles([$role]);
                    $this->users[$domain->getIdentifier().'_'.$role]->setDomain($domain);

                    $this->em->persist($this->users[$domain->getIdentifier().'_'.$role]);
                    $this->em->flush($this->users[$domain->getIdentifier().'_'.$role]);
                }
            }
        }

        $this->controller = new GraphQLApiController();
        $this->controller->setContainer($this->container);
    }

    public function testReachingMaximumNestingLevel()
    {

        $news = new Content();
        $category = new Content();
        $news->setContentType($this->domains['marketing']->getContentTypes()->first());
        $category->setContentType($this->domains['marketing']->getContentTypes()->last());

        $this->em->persist($news);
        $this->em->persist($category);
        $this->em->flush();
        $this->em->refresh($this->domains['marketing']->getContentTypes()->first());
        $this->em->refresh($this->domains['marketing']);

        $news->setData(
            [
                'category' => [
                    'domain' => $this->domains['marketing']->getIdentifier(),
                    'content_type' => 'news_category',
                    'content' => $category->getId(),
                ],
            ]
        );
        $category->setData(
            [
                'news' => [
                    'domain' => $this->domains['marketing']->getIdentifier(),
                    'content_type' => 'news',
                    'content' => $news->getId(),
                ],
            ]
        );

        $this->em->flush();
        $this->em->refresh($this->domains['marketing']->getContentTypes()->first());
        $this->em->refresh($this->domains['marketing']->getContentTypes()->last());
        $this->em->refresh($this->domains['marketing']);

        $result = $this->api(
            $this->domains['marketing'],
            $this->users['marketing_ROLE_PUBLIC'],
            'query {
                findNews {
                    result {
                      category {
                        news {
                          category {
                            news {
                              category {
                                message
                              }
                            }
                          }
                        }
                      }
                    }
                  }
            }'
        );

        $this->assertApiResponse(
            [
                'data' => [
                    'findNews' => [
                        'result' => [
                            [
                                'category' => [
                                    'news' => [
                                        'category' => [
                                            'news' => [
                                                'category' => [
                                                    'message' => 'Maximum nesting level of 5 reached.',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $result
        );
    }

    private function api(Domain $domain, UserInterface $user, string $query, array $variables = [])
    {
        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'domain');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $domain);
        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $domain->getOrganization());
        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'initialized');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), true);

        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'api', $user->getRoles())
        );

        $request = new Request(
            [], [], [
            'organization' => $domain->getOrganization(),
            'domain' => $domain,
        ], [], [], [
            'REQUEST_METHOD' => 'POST',
        ], json_encode(['query' => $query, 'variables' => $variables])
        );
        $response = $this->controller->indexAction($domain->getOrganization(), $domain, $request);

        return json_decode($response->getContent());
    }

    private function assertApiResponse($expected, $actual)
    {

        if (!is_string($expected)) {
            $expected = json_encode($expected);
        }

        $this->assertEquals(json_decode($expected), $actual);
    }
}
