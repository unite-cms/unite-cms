<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 27.09.18
 * Time: 10:20
 */

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Tests\APITestCase;
use UniteCMS\CoreBundle\Entity\Content;

class StateFieldTypeApiTest extends APITestCase
{
    protected $domainConfig = ['ct' => '{
        "content_types": [
            {
                "title": "CT",
                "identifier": "ct",
                "fields": [
                    {
                      "title": "State",
                      "identifier": "state",
                      "type": "state",
                      "settings": {
                        "initial_place": "draft",
                        "places": {
                          "draft": {
                            "label": "Draft",
                            "category": "primary"
                          },
                          "review": {
                            "label": "Review",
                            "category": "notice"
                          },
                          "review2": {
                            "label": "Review2",
                            "category": "danger"
                          },
                          "published": {
                            "label": "Published",
                            "category": "success"
                          }
                        },
                        "transitions": {
                          "to_draft": {
                            "label": "Back to draft",
                            "from": [
                              "review",
                              "review2",
                              "published"
                            ],
                            "to": "draft"
                          },
                          "to_review": {
                            "label": "To review",
                            "from": [
                              "draft"
                            ],
                            "to": "review"
                          },
                          "to_review2": {
                            "label": "To review 2",
                            "from": [
                              "review"
                            ],
                            "to": "review2"
                          },
                          "to_published": {
                            "label": "To published",
                            "from": [
                              "review",
                              "review2"
                            ],
                            "to": "published"
                          }
                        }
                      }
                   }
               ]
            }
        ]
    }'];

    public function testStateFieldTypeGraphQLRead()
    {
        $content = new Content();
        $content->setContentType($this->domains['ct']->getContentTypes()->get('ct'));
        $this->repositoryFactory->add($content);

        // test empty read
        $result = json_decode(json_encode($this->api('query {
            getCt(id: '.$content->getId().') {
                 state
            }
         }')), true);

        $this->assertNull($result['data']['getCt']['state']);

        // test set state
        $content->setData(
            [
                'state' => 'draft'
            ]
        );

        $result = json_decode(json_encode($this->api('query {
            getCt(id: '.$content->getId().') {
                 state
            }
         }')), true);

        $this->assertEquals('draft', $result['data']['getCt']['state']);
    }

    public function testStateFieldTypeGraphQLUpdate() {

        $content = new Content();
        $content->setContentType($this->domains['ct']->getContentTypes()->get('ct'));
        $this->repositoryFactory->add($content);

        // test empty update
        $query = 'mutation {
            updateCt(id: '.$content->getId().', data: {}, persist: false) {
                state
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals('draft', $response->data->updateCt->state);

        // test wrong transitions
        $query = 'mutation {
            updateCt(id: '.$content->getId().', data: { state: { transition: "to_published" }  }, persist: false) {
                state
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertFalse(empty($response->errors));
        $this->assertEquals('The given Transition is not allowed for this object.', $response->errors[0]->message);

        // test valid transitions
        $query = 'mutation {
            updateCt(id: '.$content->getId().', data: { state: { transition: "to_review" }  }, persist: false) {
                state
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertTrue(empty($response->errors));
        $this->assertEquals("review", $response->data->updateCt->state);

    }

    public function testStateFieldTypeGraphQLWrite()
    {

        // test empty state
        $query = 'mutation {
            createCt(data: {}, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals('draft', $response->data->createCt->state);

        // test wrong format
        $query = 'mutation {
            createCt(data: { state: { transition: null } }, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals('draft', $response->data->createCt->state);


        // test invalid transition
        $query = 'mutation {
            createCt(data: { state: { transition: "toxxxx_published" } }, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertFalse(empty($response->errors));
        $this->assertEquals('This value is not valid.', $response->errors[0]->message);

        // test invalid keys
        $query = 'mutation {
            createCt(data: { state: { xxx: "" , transition: "toxxxx_published" } }, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertFalse(empty($response->errors));
        $this->assertContains('Unknown field', $response->errors[0]->message);


        // test not allowed transition
        $query = 'mutation {
            createCt(data: { state: { transition: "to_published" } }, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertFalse(empty($response->errors));
        $this->assertEquals('The given Transition is not allowed for this object.', $response->errors[0]->message);

        // test allowed transition
        $query = 'mutation {
            createCt(data: { state: { transition: "to_review" } }, persist: false) {
                state
            }
        }';
        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals("review", $response->data->createCt->state);

    }
}