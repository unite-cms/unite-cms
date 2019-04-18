<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use Symfony\Component\HttpFoundation\Request;
use UniteCMS\CoreBundle\Entity\Content;
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

        // Create dummy element.
        $content = new Content();
        $content->setContentType($this->domains['ct']->getContentTypes()->get('ct'));
        $content->setData(['choices' => ['foo', 'baa']]);
        $this->repositoryFactory->add($content);

        // Make sure, that dummy element returns the correct values.
        $query = 'query($id: ID!) {
            getCt(id: $id) { choices }
        }';
        $response = $this->api($query, $this->domains['ct'], ['id' => $content->getId()]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(['foo', 'baa'], $response->data->getCt->choices);

        // Now try to delete an entry from the choices list.
        $query = 'mutation($id: ID!) {
            updateCt(id: $id, data: { choices: ["foo"] }, persist: false) {
                choices
            }
        }';
        $response = $this->api($query, $this->domains['ct'], ['id' => $content->getId()]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals(["foo"], $response->data->updateCt->choices);

        // Now try to delete all entries from the choices list.
        $query = 'mutation($id: ID!) {
            updateCt(id: $id, data: { choices: [] }, persist: false) {
                choices
            }
        }';
        $response = $this->api($query, $this->domains['ct'], ['id' => $content->getId()]);
        $this->assertTrue(empty($response->errors));
        $this->assertEquals([], $response->data->updateCt->choices);
    }
}
