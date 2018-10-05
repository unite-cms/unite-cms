<?php

namespace UniteCMS\VariantsFieldBundle\Tests;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class VariantsFieldAvailable extends DatabaseAwareTestCase
{
    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Domain
     */
    private $domain;

    private $domainConfiguration = '{
      "title": "Test Variants Field Domain",
      "identifier": "domain",
      "content_types": [
        {
          "title": "Variants",
          "identifier": "variants",
          "content_label": "{type} #{id}",
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
                      }
                    ]
                  }
                ]
              }
            }
          ]
        }
      ]
    }';

    public function setUp()
    {
        parent::setUp();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test wekhooks')->setIdentifier('webhook_test');

        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
    }

    public function testFieldIsAvailableOutsideOfAPIController()
    {

        $content = new Content();
        $content
            ->setContentType($this->domain->getContentTypes()->first())
            ->setData([]);

        // Create GraphQL Schema
        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($this->domain, ucfirst($content->getContentType()->getIdentifier()) . 'Content');
        $result = GraphQL::executeQuery($schema, '
            query {
                variants {
                    type
                    ... on VariantsContentVariantsVariant_1Variant {
                      field_text
                    }
                }
            }', $content);

        // Make sure, that variants type (a nonDetectable type) can be found.
        $this->assertTrue(empty($result->errors));
    }
}
