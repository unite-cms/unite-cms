<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use UniteCMS\CoreBundle\Tests\APITestCase;

class ApiEmptyTypesTest extends APITestCase
{
    protected $domainConfig = [
        'empty' => '{}',
        'content_type' => '{ "content_types": [ { "title": "CT", "identifier": "ct" } ] }',
        'setting_type' => '{ "setting_types": [ { "title": "ST", "identifier": "st" } ] }',
        'both_types' => '{ "content_types": [ { "title": "CT", "identifier": "ct" } ], "setting_types": [ { "title": "ST", "identifier": "st" } ] }',
        'no_access' => '{ "content_types": [ { 
            "title": "CT", 
            "identifier": "ct", 
            "fields": [
                { "title": "ref", "identifier": "ref", "type": "reference", "settings": { "domain": "no_access", "content_type": "ct" } },
                { "title": "ref_of", "identifier": "ref_of", "type": "reference_of", "settings": { "domain": "no_access", "content_type": "ct", "reference_field": "ref" } }
            ],
            "permissions": { "list content": "false" } 
        }, { "title": "Other CT", "identifier": "other_ct" } ], "setting_types": [ { 
            "title": "ST", 
            "identifier": "st",
            "fields": [{ "title": "ref", "identifier": "ref", "type": "reference", "settings": { "domain": "no_access", "content_type": "ct" } }],
            "permissions": { "view setting": "false" } 
        }, { "title": "Other ST", "identifier": "other_st" } ] }',
    ];

    public function testIntrospectionQueryForEmpty() {

        $query = 'query {
            __schema {
                queryType { name }
                mutationType { name }
                subscriptionType { name }
                types {
                  kind,
                  name,
                  fields {
                    name
                  }
                }
            }
        }';

        $response = $this->api($query, $this->domains['empty']);

        $this->assertFalse(isset($response->errors));
        $this->assertNotNull($response->data->__schema->queryType);
        $this->assertNull($response->data->__schema->mutationType);

        // type fields most not be empty.
        foreach($response->data->__schema->types as $type) {
            if($type->kind === 'OBJECT') {
                $this->assertNotEmpty($type->fields);
            }
        }
    }

    public function testIntrospectionQueryForContentType() {

        $query = 'query {
            __schema {
                queryType { name }
                mutationType { name }
                subscriptionType { name }
                types {
                  kind,
                  name,
                  fields {
                    name
                  }
                }
            }
        }';

        $response = $this->api($query, $this->domains['content_type']);

        $this->assertFalse(isset($response->errors));
        $this->assertNotNull($response->data->__schema->queryType);
        $this->assertNotNull($response->data->__schema->mutationType);

        // type fields most not be empty.
        foreach($response->data->__schema->types as $type) {
            if($type->kind === 'OBJECT') {
                $this->assertNotEmpty($type->fields);
            }
        }
    }

    public function testIntrospectionQueryForSettingType() {

        $query = 'query {
            __schema {
                queryType { name }
                mutationType { name }
                subscriptionType { name }
                types {
                  kind,
                  name,
                  fields {
                    name
                  }
                }
            }
        }';

        $response = $this->api($query, $this->domains['setting_type']);

        $this->assertFalse(isset($response->errors));
        $this->assertNotNull($response->data->__schema->queryType);
        $this->assertNull($response->data->__schema->mutationType);

        // type fields most not be empty.
        foreach($response->data->__schema->types as $type) {
            if($type->kind === 'OBJECT') {
                $this->assertNotEmpty($type->fields);
            }
        }
    }

    public function testIntrospectionQueryForBothTypes() {

        $query = 'query {
            __schema {
                queryType { name }
                mutationType { name }
                subscriptionType { name }
                types {
                  kind,
                  name,
                  fields {
                    name
                  }
                }
            }
        }';

        $response = $this->api($query, $this->domains['both_types']);

        $this->assertFalse(isset($response->errors));
        $this->assertNotNull($response->data->__schema->queryType);
        $this->assertNotNull($response->data->__schema->mutationType);

        // type fields most not be empty.
        foreach($response->data->__schema->types as $type) {
            if($type->kind === 'OBJECT') {
                $this->assertNotEmpty($type->fields);
            }
        }
    }

    public function testQueryWithoutListAccess()
    {

        $query = 'query {
            __schema {
                queryType { name }
                mutationType { name }
                subscriptionType { name }
                types {
                  kind,
                  name,
                  fields {
                    name
                  }
                }
            }
        }';

        $response = $this->api($query, $this->domains['no_access']);
        $this->assertTrue(empty($response->errors));

        $definedTypes = [];
        $queryFields = [];
        $mutationFields = [];

        foreach($response->data->__schema->types as $type) {
            $definedTypes[] = $type->name;

            if($type->name === 'Query') {
                $queryFields = array_map(function($f){ return $f->name; }, $type->fields);
            }

            if($type->name === 'Mutation') {
                $mutationFields = array_map(function($f){ return $f->name; }, $type->fields);
            }
        }

        $this->assertNotContains('CtContent', $definedTypes);
        $this->assertNotContains('CtContentPermissions', $definedTypes);
        $this->assertNotContains('CtContentResult', $definedTypes);
        $this->assertNotContains('CtContentResultPermissions', $definedTypes);
        $this->assertNotContains('StSetting', $definedTypes);
        $this->assertNotContains('StSettingPermissions', $definedTypes);

        $this->assertNotContains('getCt', $queryFields);
        $this->assertNotContains('findCt', $queryFields);
        $this->assertNotContains('StSetting', $queryFields);

        $this->assertNotContains('createCt', $mutationFields);
        $this->assertNotContains('updateCt', $mutationFields);
        $this->assertNotContains('deleteCt', $mutationFields);
    }
}
