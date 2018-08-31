<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 31.08.18
 * Time: 10:05
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DomainDefinitionParserTest extends DatabaseAwareTestCase
{
    // NOTE: parsing the domain is tested in CreateDomainCommandTest.

    private $configVariables = '{
        "@var_title": "replaced_title", 
        "@var_fields": [ { "title": "F1", "identifier": "f1", "type": "text" }, { "title": "F2", "identifier": "f2", "type": "text" } ], 
        "@ct3": { "title": "T3",  "identifier": "t3", "content_label": "{type} #{id}", "fields": [], "views": [ { "title": "All", "identifier": "all", "type": "table" } ], "permissions": { "view content": "true", "list content": "true", "create content": "member.type == \"editor\"", "update content": "member.type == \"editor\"", "delete content": "member.type == \"editor\"" } } 
    }';

    private $validDomainWithVariables = '{ 
        "title": "@var_title", 
        "identifier": "with_variables", 
        "content_types": [
            { 
                "title": "T1",  
                "identifier": "t1", 
                "content_label": "{type} #{id}", 
                "fields": "@var_fields", 
                "views": [ { "title": "All", "identifier": "all", "type": "table" } ], 
                "permissions": { "view content": "true", "list content": "true", "create content": "member.type == \"editor\"", "update content": "member.type == \"editor\"", "delete content": "member.type == \"editor\"" } 
            }, 
            { 
                "title": "T2", 
                "identifier": "t2", 
                "content_label": "{type} #{id}", 
                "fields": "@var_fields", "views": [ { "title": "All", "identifier": "all", "type": "table" } ], 
                "permissions": { "view content": "true", "list content": "true", "create content": "member.type == \"editor\"", "update content": "member.type == \"editor\"", "delete content": "member.type == \"editor\"" } 
            }, 
            "@ct3"
        ],
        "setting_types": [],
        "domain_member_types": [
            {
                "title": "Editor",
                "identifier": "editor",
                "domain_member_label": "{accessor}",
                "fields": []
            },
            {
                "title": "Viewer",
                "identifier": "viewer",
                "domain_member_label": "{accessor}",
                "fields": []
            }
        ],
        "permissions": {
            "view domain": "true",
            "update domain": "false"
        }
    }';

    public function testDomainSerialization() {

        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->validDomainWithVariables, $this->configVariables);
        $domain->setOrganization($org);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();

        /**
         * @var Domain[]
         */
        $domains = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll();
        $this->assertCount(1, $domains);

        $parsedDomain = static::$container->get('unite.cms.domain_definition_parser')->serialize($domain);
        $this->assertJsonStringEqualsJsonString($this->validDomainWithVariables, $parsedDomain);
        $this->assertJsonStringEqualsJsonString($this->configVariables, json_encode($domain->getConfigVariables()));
    }
}