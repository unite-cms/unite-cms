<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 06.08.18
 * Time: 08:57
 */

namespace UniteCMS\VariantsFieldBundle\Tests;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Tests\APITestCase;

class VariantsGraphQLTest extends APITestCase
{
    protected $domainConfig = ['variants' => '{
        "content_types": [
            {
              "title": "Variants",
              "identifier": "variants",
              "fields": [
                {
                  "title": "Variants",
                  "identifier": "variants",
                  "type": "variants",
                  "settings": {
                    "variants": [
                        {
                            "title": "V1",
                            "identifier": "v1",
                            "fields": [
                                {
                                    "title": "Text",
                                    "identifier": "text",
                                    "type": "text"
                                }
                            ]
                        },
                        {
                            "title": "V2",
                            "identifier": "v2",
                            "fields": [
                                {
                                    "title": "Collection",
                                    "identifier": "collection",
                                    "type": "collection",
                                    "settings": {
                                        "fields": [
                                            {
                                                "title": "Text",
                                                "identifier": "text",
                                                "type": "text"
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    ]
                  }
                }
              ]
            }
        ],
        "setting_types": [
        {
          "title": "Variants",
          "identifier": "variants",
          "fields": [
            {
              "title": "Variants",
              "identifier": "variants",
              "type": "variants",
              "settings": {
                "variants": [
                    {
                        "title": "V1",
                        "identifier": "v1",
                        "fields": [
                            {
                                "title": "Text",
                                "identifier": "text",
                                "type": "text"
                            }
                        ]
                    }
                ]
              }
            }
          ]
        }
        ]
    }',
    ];

    public function testQueryVariants() {

        $c = new Content();
        $c->setContentType($this->domains['variants']->getContentTypes()->first());
        $this->repositoryFactory->add($c);

        // 1. Content without any field data.
        $query = 'query {
            findVariants {
                result {
                    variants {
                        type,
                        
                        ... on VariantsContentVariantsV1Variant {
                            text
                        }
                        
                        ... on VariantsContentVariantsV2Variant {
                            collection {
                                text
                            }
                        }
                    }
                }
            },
            getVariants(id: '.$c->getId().') {
                variants {
                    type,
                    
                    ... on VariantsContentVariantsV1Variant {
                        text
                    }
                    
                    ... on VariantsContentVariantsV2Variant {
                        collection {
                            text
                        }
                    }
                }
            }
        }';

        $this->assertEquals([
            'data' => [
                'findVariants' => [
                    'result' => [
                        [
                            'variants' => [
                                'type' => null,
                            ]
                        ]
                    ]
                ],
                'getVariants' => [
                    'variants' => [
                        'type' => null,
                    ]
                ],
            ]], json_decode(json_encode($this->api($query)), true));
        
        // 2. Content with field data.
        $c->setData([
            'variants' => [
                'type' => 'v2',
                'v2' => [
                    'collection' => [
                        ['text' => 'Foo'],
                        ['text' => 'Baa'],
                    ]
                ],
            ]
        ]);

        $this->assertEquals([
            'data' => [
                'findVariants' => [
                    'result' => [
                        [
                            'variants' => [
                                'type' => 'v2',
                                'collection' => [
                                    ['text' => 'Foo'],
                                    ['text' => 'Baa'],
                                ]
                            ]
                        ]
                    ]
                ],
                'getVariants' => [
                    'variants' => [
                        'type' => 'v2',
                        'collection' => [
                            ['text' => 'Foo'],
                            ['text' => 'Baa'],
                        ]
                    ]
                ],
            ]], json_decode(json_encode($this->api($query)), true));

        // 3. Settings without any field data.

        // 4. Settings with field data.

    }

    public function testMutateVariants() {

        // Create nested content object.

        // Create settings object.

    }
}