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
    private $allowed_level = 8;

    protected $domainConfig = ['marketing' => '{
        "content_types": [
            {
                "title": "Self",
                "identifier": "self",
                "fields": [
                  {
                      "title": "Sibling",
                      "identifier": "sibling",
                      "type": "reference",
                      "settings": {
                        "domain": "marketing",
                        "content_type": "self"
                      }
                    },
                    
                    {
                      "title": "Collection",
                      "identifier": "collection",
                      "type": "collection",
                      "settings": {
                        "fields": [
                        {
                            "title": "Sibling",
                            "identifier": "sibling",
                            "type": "reference",
                            "settings": {
                                "domain": "marketing",
                                "content_type": "self"
                            }
                        }
                        ]
                    }
                    }
                ]
            },
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

        $this->allowed_level = static::$container->get('unite.cms.graphql.schema_type_manager')->getMaximumNestingLevel();

        $news = new Content();
        $category = new Content();
        $news->setContentType($this->domains['marketing']->getContentTypes()->get('news'));
        $category->setContentType($this->domains['marketing']->getContentTypes()->get('news_category'));

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
                                news {
                                  id
                                }
                              }
                            }
                          }
                        }
                      }
                    }
                  }
            }')), true);

        $this->assertEquals([
            'errors' => [
                [
                    'message' => 'Max query depth should be ' . $this->allowed_level . ' but got ' . ($this->allowed_level + 1) . '.',
                    'extensions' => [
                        'category' => 'graphql',
                    ],
                ]
            ]
        ], $result);
    }

    public function testReachingMaximumNestingLevelForSelfReference() {

        $this->allowed_level = static::$container->get('unite.cms.graphql.schema_type_manager')->getMaximumNestingLevel();

        $self = new Content();
        $sibling = new Content();
        $self->setContentType($this->domains['marketing']->getContentTypes()->get('self'));
        $sibling->setContentType($this->domains['marketing']->getContentTypes()->get('self'));

        $this->repositoryFactory->add($self);
        $this->repositoryFactory->add($sibling);

        $self->setData(['sibling' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'self', 'content' => $sibling->getId()]]);
        $sibling->setData(['sibling' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'self', 'content' => $self->getId()]]);

        $result = json_decode(json_encode($this->api('query {
                getSelf(id: "'.$self->getId().'") {
                    sibling {
                        sibling {
                          sibling {
                            sibling {
                              sibling {
                                sibling {
                                  sibling {
                                    id
                                  }  
                                }
                              }
                            }
                          }
                        }
                      }
                  }
            }')), true);

        $this->assertEquals([
            'errors' => [
                [
                    'message' => 'Max query depth should be ' . $this->allowed_level . ' but got ' . ($this->allowed_level + 1) . '.',
                    'extensions' => [
                        'category' => 'graphql',
                    ],
                ]
            ]
        ], $result);
    }

    public function testReachingMaximumNestingLevelForSelfReferenceInCollection() {

        $this->allowed_level = static::$container->get('unite.cms.graphql.schema_type_manager')->getMaximumNestingLevel();

        $self = new Content();
        $sibling = new Content();
        $self->setContentType($this->domains['marketing']->getContentTypes()->get('self'));
        $sibling->setContentType($this->domains['marketing']->getContentTypes()->get('self'));

        $this->repositoryFactory->add($self);
        $this->repositoryFactory->add($sibling);

        $self->setData([
            'collection' => [
                [
                    'sibling' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'self', 'content' => $sibling->getId()]
                ]
            ]
        ]);
        $sibling->setData([
            'collection' => [
                [
                    'sibling' => ['domain' => $this->domains['marketing']->getIdentifier(), 'content_type' => 'self', 'content' => $self->getId()]
                ]
            ]
        ]);


        $result = json_decode(json_encode($this->api('query {
            getSelf(id: "'.$self->getId().'") {
                collection {
                    sibling {
                        collection {
                            sibling {
                                collection {
                                    sibling {
                                        sibling {
                                            id
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }')), true);

        $this->assertEquals([
            'errors' => [
                [
                    'message' => 'Max query depth should be ' . $this->allowed_level . ' but got ' . ($this->allowed_level + 1) . '.',
                    'extensions' => [
                        'category' => 'graphql',
                    ],
                ]
            ]
        ], $result);
    }
}
