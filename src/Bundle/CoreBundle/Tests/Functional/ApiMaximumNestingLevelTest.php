<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Tests\APITestCase;

/**
 * @group slow
 */
class ApiMaximumNestingLevelTest extends APITestCase
{

    protected $domainConfig = ['marketing' => '{
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
              ]
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
              ]
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
          ]
        }
    ]
    }'];

    public function testReachingMaximumNestingLevel() {

        $news = new Content();
        $category = new Content();
        $news->setContentType($this->domains['marketing']->getContentTypes()->first());
        $category->setContentType($this->domains['marketing']->getContentTypes()->last());

        $this->repositoryFactory->add($news);
        $this->repositoryFactory->add($category);

        $news->setData(['category' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'news_category', 'content' => $category->getId()]]);
        $category->setData(['news' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'news', 'content' => $news->getId()]]);

        $result = json_decode(json_encode($this->api('query {
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
            }')), true);

        $this->assertEquals([
            'data' => [
                'findNews' => [
                    'result' => [[
                        'category' => [
                            'news' => [
                                'category' => [
                                    'news' => [
                                        'category' => [
                                            'message' => 'Maximum nesting level of 5 reached.',
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ]],
                ]
            ]
        ], $result);
    }
}
