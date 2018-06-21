<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.01.18
 * Time: 16:55
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Controller\GraphQLApiController;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\Tests\APITestCase;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ApiEmptyTypesTest extends APITestCase
{
    protected $domainConfig = [
        'empty' => '{}',
        'content_type' => '{ "content_types": [ { "title": "CT", "identifier": "ct" } ] }',
        'setting_type' => '{ "setting_types": [ { "title": "ST", "identifier": "st" } ] }',
        'both_types' => '{ "content_types": [ { "title": "CT", "identifier": "ct" } ], "setting_types": [ { "title": "ST", "identifier": "st" } ] }',
    ];

    public function testIntrospectionQuery() {

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
}
