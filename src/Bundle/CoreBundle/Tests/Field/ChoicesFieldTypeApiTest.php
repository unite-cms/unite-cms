<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Tests\APITestCase;

class ChoicesFieldTypeApiTest extends APITestCase
{
    protected $domainConfig = ['ct' => '{
        "content_types": [
            {
                "title": "CT", 
                "identifier": "ct",
                "fields": [
                    {
                      "title": "Choices",
                      "identifier": "choices",
                      "type": "choices",
                      "settings": {
                        "choices": {
                          "Foo": "foo",
                          "Baa": "baa"
                        }
                      }
                    }
                ]
            }
        ]
    }'];

    public function testGraphQLReadAndWrite()
    {
        $query = 'mutation {
            createCt(data: {}, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals([], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: "foo" }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(['foo'], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: [] }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals([], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: ["foo"] }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(["foo"], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: ["foo", "baa"] }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(["foo", "baa"], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: ["baa", "foo"] }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(["foo", "baa"], $response->data->createCt->choices);

        $query = 'mutation {
            createCt(data: { choices: ["any", "foo"] }, persist: false) {
                choices
            }
        }';

        $response = $this->api($query, $this->domains['ct']);
        $this->assertFalse(empty($response->errors));
        $this->assertEquals('This value is not valid.', $response->errors[0]->message);
        $this->assertEmpty($response->data->createCt);
    }
}
