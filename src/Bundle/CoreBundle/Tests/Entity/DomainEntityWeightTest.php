<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 27.04.18
 * Time: 10:53
 */

namespace App\Bundle\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class DomainEntityWeightTest extends ContainerAwareTestCase
{

    private function assertCorrectWeights(Domain $domain) {

        $weight = 0;

        foreach($domain->getContentTypes() as $contentType) {
            $this->assertEquals($weight, $contentType->getWeight());
            $weight++;

            $fWeight = 0;

            foreach($contentType->getFields() as $field) {
                $this->assertEquals($fWeight, $field->getWeight());
                $fWeight++;
            }
        }

        $weight = 0;

        foreach($domain->getSettingTypes() as $settingType) {
            $this->assertEquals($weight, $settingType->getWeight());
            $weight++;

            $fWeight = 0;

            foreach($settingType->getFields() as $field) {
                $this->assertEquals($fWeight, $field->getWeight());
                $fWeight++;
            }
        }
    }

    public function testWeightUpdateAfterDeserialization()
    {

        $domain = $this->container->get('unite.cms.domain_definition_parser')->parse(
            '{
            "title": "Test",
            "identifier": "test",
            "content_types": [
                {
                    "title": "CT1",
                    "identifier": "ct1",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                },
                {
                    "title": "CT2",
                    "identifier": "ct2",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                }
            ],
            "setting_types": [
                {
                    "title": "ST1",
                    "identifier": "st1",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                },
                {
                    "title": "ST2",
                    "identifier": "st2",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                }
            ]
        }'
        );

        $this->assertCorrectWeights($domain);


        // Now try to update an empty existing domain with this new domain.
        $existingDomain = new Domain();
        $existingDomain->setFromEntity($domain);

        $this->assertCorrectWeights($existingDomain);

        // Let's move ct1, st1 and first fields of ct1 and st1 to the end.
        $domain = $this->container->get('unite.cms.domain_definition_parser')->parse(
            '{
            "title": "Test",
            "identifier": "test",
            "content_types": [
                {
                    "title": "CT2",
                    "identifier": "ct2",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                },
                {
                    "title": "CT1",
                    "identifier": "ct1",
                    "fields": [
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        },
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        }
                    ]
                }
            ],
            "setting_types": [
                {
                    "title": "ST2",
                    "identifier": "st2",
                    "fields": [
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        },
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        }
                    ]
                },
                {
                    "title": "ST1",
                    "identifier": "st1",
                    "fields": [
                        {
                          "title": "F2",
                          "identifier": "f2",
                          "type": "text"
                        },
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        },
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        }
                    ]
                }
            ]
        }'
        );

        $existingDomain->setFromEntity($domain);
        $this->assertEquals(1, $existingDomain->getContentTypes()->get('ct1')->getWeight());
        $this->assertEquals(0, $existingDomain->getContentTypes()->get('ct2')->getWeight());

        $this->assertEquals(2, $existingDomain->getContentTypes()->get('ct1')->getFields()->get('f1')->getWeight());
        $this->assertEquals(0, $existingDomain->getContentTypes()->get('ct1')->getFields()->get('f2')->getWeight());
        $this->assertEquals(1, $existingDomain->getContentTypes()->get('ct1')->getFields()->get('f3')->getWeight());

        $this->assertEquals(1, $existingDomain->getSettingTypes()->get('st1')->getWeight());
        $this->assertEquals(0, $existingDomain->getSettingTypes()->get('st2')->getWeight());

        $this->assertEquals(2, $existingDomain->getSettingTypes()->get('st1')->getFields()->get('f1')->getWeight());
        $this->assertEquals(0, $existingDomain->getSettingTypes()->get('st1')->getFields()->get('f2')->getWeight());
        $this->assertEquals(1, $existingDomain->getSettingTypes()->get('st1')->getFields()->get('f3')->getWeight());

        // Let's delete ct2, st2 and f2 of ct1 and st1
        // Let's move ct1, st1 and first fields of ct1 and st1 to the end.
        $domain = $this->container->get('unite.cms.domain_definition_parser')->parse(
            '{
            "title": "Test",
            "identifier": "test",
            "content_types": [
                {
                    "title": "CT1",
                    "identifier": "ct1",
                    "fields": [
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        },
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        }
                    ]
                }
            ],
            "setting_types": [
                {
                    "title": "ST1",
                    "identifier": "st1",
                    "fields": [
                        {
                          "title": "F3",
                          "identifier": "f3",
                          "type": "text"
                        },
                        {
                          "title": "F1",
                          "identifier": "f1",
                          "type": "text"
                        }
                    ]
                }
            ]
        }'
        );

        $existingDomain->setFromEntity($domain);
        $this->assertEquals(0, $existingDomain->getContentTypes()->get('ct1')->getWeight());

        $this->assertEquals(1, $existingDomain->getContentTypes()->get('ct1')->getFields()->get('f1')->getWeight());
        $this->assertEquals(0, $existingDomain->getContentTypes()->get('ct1')->getFields()->get('f3')->getWeight());

        $this->assertEquals(0, $existingDomain->getSettingTypes()->get('st1')->getWeight());

        $this->assertEquals(1, $existingDomain->getSettingTypes()->get('st1')->getFields()->get('f1')->getWeight());
        $this->assertEquals(0, $existingDomain->getSettingTypes()->get('st1')->getFields()->get('f3')->getWeight());
    }
}