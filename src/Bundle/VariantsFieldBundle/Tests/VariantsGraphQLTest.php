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
                                },
                                {
                                    "title": "Text",
                                    "identifier": "hidden_text",
                                    "type": "text",
                                    "permissions": {
                                        "list field": "false"
                                    }
                                },
                                {
                                    "title": "Text",
                                    "identifier": "denied_text",
                                    "type": "text",
                                    "permissions": {
                                        "view field": "false"
                                    }
                                },
                                {
                                    "title": "Text",
                                    "identifier": "denied_update_text",
                                    "type": "text",
                                    "permissions": {
                                        "view field": "true",
                                        "update field": "false"
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
                "not_empty": true,
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
                            },
                            denied_text
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
                        },
                        denied_text
                    }
                }
            },
            __type(name: "VariantsContentVariantsVariant_2Variant") {
                fields {
                  name
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
                '__type' => [
                    'fields' => [
                        ['name' => 'type'],
                        ['name' => 'collection'],
                        ['name' => 'ref'],
                        ['name' => 'denied_text'],
                        ['name' => 'denied_update_text'],
                    ]
                ]
            ]], json_decode(json_encode($this->api($query)), true));

        // 2. Content with field data.
        $c->setData([
            'variants' => [
                'type' => 'variant_2',
                'variant_2' => [
                    'collection' => [
                        ['field_text' => 'Foo'],
                        ['field_text' => 'Baa'],
                    ],
                    'hidden_text' => 'Hidden',
                    'denied_text' => 'Denied',
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
                                ],
                                'denied_text' => null,
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
                        ],
                        'denied_text' => null,
                    ]
                ],
                '__type' => [
                    'fields' => [
                        ['name' => 'type'],
                        ['name' => 'collection'],
                        ['name' => 'ref'],
                        ['name' => 'denied_text'],
                        ['name' => 'denied_update_text'],
                    ]
                ]
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
                    'text' => 'Foo',
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
                    variant_2: { denied_update_text: "DENIED", collection: [ { field_text: "Foo" }, { field_text: "Baa" } ], ref: { content_type: "other", domain: "variants", content: "'.$other_c1->getId().'" } }
                } }, persist: false) {
                    variants {
                        type,
                        
                        ... on VariantsContentVariantsVariant_2Variant {
                            collection {
                                field_text
                            },
                            denied_text,
                            denied_update_text
                        }
                    }
                }
            }')), true);

        $this->assertEquals('This form should not contain extra fields.', $response['errors'][0]['message']);

        $response = json_decode(json_encode($this->api('mutation {
                createVariants(data: { variants: {
                    type: "variant_2",
                    variant_1: { field_text: "Foo" },
                    variant_2: { denied_text: "DENIED", collection: [ { field_text: "Foo" }, { field_text: "Baa" } ], ref: { content_type: "other", domain: "variants", content: "'.$other_c1->getId().'" } }
                } }, persist: false) {
                    variants {
                        type,
                        
                        ... on VariantsContentVariantsVariant_2Variant {
                            collection {
                                field_text
                            },
                            denied_text,
                            denied_update_text
                        }
                    }
                }
            }')), true);

        $this->assertEquals([
            'data' => [
                'createVariants' => [
                    'variants' => [
                        'type' => 'variant_2',
                        'collection' => [
                            ['field_text' => 'Foo'],
                            ['field_text' => 'Baa'],
                        ],
                        'denied_text' => null,
                        'denied_update_text' => null,
                    ]
                ],
            ]], $response);

        $response = json_decode(json_encode($this->api('mutation {
            updateVariantsSetting(data: { variants: {} }, persist: true) {
                variants {
                    type
                }
            }
        }')));
        $this->assertEquals('This field is required.', $response->errors[0]->message);
        $this->assertEquals(['updateVariantsSetting', 'data', 'variants'], $response->errors[0]->path);
        $this->assertEmpty($response->data->updateVariantsSetting);

        $response = json_decode(json_encode($this->api('mutation {
            updateVariantsSetting(data: { variants: { type: null } }, persist: true) {
                variants {
                    type
                }
            }
        }')));
        $this->assertEquals('This field is required.', $response->errors[0]->message);
        $this->assertEquals(['updateVariantsSetting', 'data', 'variants'], $response->errors[0]->path);
        $this->assertEmpty($response->data->updateVariantsSetting);

        $response = json_decode(json_encode($this->api('mutation {
            updateVariantsSetting(data: { variants: { type: "v1" } }, persist: true) {
                variants {
                    type
                }
            }
        }')));
        $this->assertFalse(isset($response->errors));
        $this->assertEquals((object)[
            'variants' => (object)[
                'type' => 'v1',
            ],
        ], $response->data->updateVariantsSetting);
    }
}