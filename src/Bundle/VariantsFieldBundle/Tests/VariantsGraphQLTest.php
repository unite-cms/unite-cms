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
                            "identifier": "variant_1",
                            "fields": [
                                {
                                    "title": "Text",
                                    "identifier": "field_text",
                                    "type": "text"
                                }
                            ]
                        },
                        {
                            "title": "V2",
                            "identifier": "variant_2",
                            "fields": [
                                {
                                    "title": "Collection",
                                    "identifier": "collection",
                                    "type": "collection",
                                    "settings": {
                                        "fields": [
                                            {
                                                "title": "Text",
                                                "identifier": "field_text",
                                                "type": "text"
                                            }
                                        ]
                                    }
                                },
                                {
                                    "title": "Ref",
                                    "identifier": "ref",
                                    "type": "reference",
                                    "settings": {
                                        "domain": "variants",
                                        "content_type": "other"
                                    }
                                }
                            ]
                        }
                    ]
                  }
                }
              ]
            },
            {
                "title": "Other",
                "identifier": "other",
                "views": [
                    {
                        "title": "All",
                        "identifier": "all",
                        "type": "table"
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

        $s = $this->domains['variants']->getSettingTypes()->first()->getSetting();

        // 1. Content without any field data.
        $query = 'query {
            findVariants {
                result {
                    variants {
                        type,
                        
                        ... on VariantsContentVariantsVariant_1Variant {
                            field_text
                        }
                        
                        ... on VariantsContentVariantsVariant_2Variant {
                            collection {
                                field_text
                            }
                        }
                    }
                }
            },
            getVariants(id: "'.$c->getId().'") {
                variants {
                    type,
                    
                    ... on VariantsContentVariantsVariant_1Variant {
                        field_text
                    }
                    
                    ... on VariantsContentVariantsVariant_2Variant {
                        collection {
                            field_text
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
                'type' => 'variant_2',
                'variant_2' => [
                    'collection' => [
                        ['field_text' => 'Foo'],
                        ['field_text' => 'Baa'],
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
                                'type' => 'variant_2',
                                'collection' => [
                                    ['field_text' => 'Foo'],
                                    ['field_text' => 'Baa'],
                                ]
                            ]
                        ]
                    ]
                ],
                'getVariants' => [
                    'variants' => [
                        'type' => 'variant_2',
                        'collection' => [
                            ['field_text' => 'Foo'],
                            ['field_text' => 'Baa'],
                        ]
                    ]
                ],
            ]], json_decode(json_encode($this->api($query)), true));

        // 3. Settings without any field data.
        $query = 'query {
            VariantsSetting {
                variants {
                    type,
                    
                    ... on VariantsSettingVariantsV1Variant {
                        text
                    }
                }
            }
        }';

        $this->assertEquals([
            'data' => [
                'VariantsSetting' => [
                    'variants' => [
                        'type' => null,
                    ]
                ]
            ]], json_decode(json_encode($this->api($query)), true));

        // 4. Settings with field data.
        $s->setData([
            'variants' => [
                'type' => 'v1',
                'v1' => [
                    'text' => 'Foo'
                ],
            ]
        ]);

        $this->assertEquals([
            'data' => [
                'VariantsSetting' => [
                    'variants' => [
                        'type' => 'v1',
                        'text' => 'Foo'
                    ],
                ],
            ]], json_decode(json_encode($this->api($query)), true));

    }

    public function testMutateVariants() {

        $other_c1 = new Content();
        $other_c1->setContentType($this->domains['variants']->getContentTypes()->last());
        $this->repositoryFactory->add($other_c1);
        $this->repositoryFactory->add($this->domains['variants']->getContentTypes()->last()->getViews()->first());

        $response = json_decode(json_encode($this->api('mutation {
                createVariants(data: { variants: {
                    type: "variant_2",
                    variant_1: { field_text: "Foo" },
                    variant_2: { collection: [ { field_text: "Foo" }, { field_text: "Baa" } ], ref: { content_type: "other", domain: "variants", content: "'.$other_c1->getId().'" } }
                } }, persist: false) {
                    variants {
                        type,
                        
                        ... on VariantsContentVariantsVariant_2Variant {
                            collection {
                                field_text
                            }
                        }
                    }
                }
            }')), true);

        // Create nested content object.
        $this->assertEquals([
            'data' => [
                'createVariants' => [
                    'variants' => [
                        'type' => 'variant_2',
                        'collection' => [
                            ['field_text' => 'Foo'],
                            ['field_text' => 'Baa'],
                        ]
                    ]
                ],
            ]], $response);
    }
}