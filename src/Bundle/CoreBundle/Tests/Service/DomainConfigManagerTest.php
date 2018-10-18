<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.10.18
 * Time: 11:45
 */

namespace App\Bundle\CoreBundle\Tests\Service;


use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldablePreview;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

class DomainConfigManagerTest extends KernelTestCase
{
    /**
     * @var DomainConfigManager $manager
     */
    private $manager;

    /**
     * @var Filesystem $fileSystem
     */
    private $fileSystem;

    /**
     * @var string $exampleConfig
     */
    private $exampleConfig;

    public function setUp() {
        parent::setUp();
        static::bootKernel();
        $this->manager = static::$container->get('unite.cms.domain_config_manager');
        $this->fileSystem = static::$container->get('filesystem');
        $this->exampleConfig = '{
            "title": "My test Domain",
            "identifier": "my_test_domain",
            "permissions": {
                "view domain": "true",
                "update domain": "true"
            },
            "content_types": [
                {
                    "title": "First Content Type",
                    "identifier": "first_ct",
                    "preview": {
                        "url": "https://example.com",
                        "query": "query { type }"
                    },
                    "content_label": "{title}",
                    "fields": [
                        {
                            "title": "Title",
                            "identifier": "title",
                            "type": "text"
                        },
                        {
                          "title": "Slug",
                          "identifier": "slug",
                          "type": "text"
                        },
                        {
                          "title": "Position",
                          "identifier": "position",
                          "type": "sortindex"
                        }
                    ],
                    "views": [
                        {
                          "title": "All",
                          "identifier": "all",
                          "type": "sortable",
                          "settings": {
                            "sort_field": "position"
                          }
                        }
                    ],
                    "permissions": {
                        "delete content": "false"
                    },
                    "webhooks": [
                        {
                          "query": "query { type }",
                          "url": "https://example.com",
                          "check_ssl": true,
                          "condition": "true"
                        }
                    ]
                },
                {
                    "title": "Other Content Type",
                    "identifier": "other_ct",
                    "icon": "file"
                }
            ],
            "setting_types": [
                {
                    "title": "First Setting Type",
                    "identifier": "first_st",
                    "icon": "file",
                    "fields": [
                        {
                            "title": "Title",
                            "identifier": "title",
                            "type": "text"
                        }
                    ],
                    "permissions": {
                        "update setting": "false"
                    },
                    "webhooks": [
                        {
                          "query": "query { type }",
                          "url": "https://example.com",
                          "check_ssl": true,
                          "condition": "true"
                        }
                    ]
                },
                {
                    "title": "Other Setting Type",
                    "identifier": "other_st"
                }
            ],
            "domain_member_types": [
                {
                    "title": "Any",
                    "identifier": "any",
                    "domain_member_label": "Any",
                    "fields": [
                        {
                            "title": "Title",
                            "identifier": "title",
                            "type": "text"
                        }
                    ]
                }
            ]
        }';
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage You can only process domains where the organization is not empty.
     */
    public function testParsingDomainWithMissingOrganization() {
        $domain = new Domain();
        $domain->setIdentifier('my_test_domain');
        $this->manager->updateDomainFromConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage You can only process domains where the organization identifier is not empty.
     */
    public function testParsingDomainWithMissingOrganizationIdentifier() {

        $organization = new Organization();
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');
        $this->manager->updateDomainFromConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingDomainException
     * @expectedExceptionMessage You can only process domains where the identifier is not empty.
     */
    public function testParsingDomainWithMissingDomainIdentifier() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain->setOrganization($organization);
        $this->manager->updateDomainFromConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage You can only process domains where the organization is not empty.
     */
    public function testDumpingDomainWithMissingOrganization() {
        $domain = new Domain();
        $domain->setIdentifier('my_test_domain');
        $this->manager->dumpDomainToConfig($domain, false);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage You can only process domains where the organization identifier is not empty.
     */
    public function testDumpingDomainWithMissingOrganizationIdentifier() {

        $organization = new Organization();
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');
        $this->manager->dumpDomainToConfig($domain, false);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingDomainException
     * @expectedExceptionMessage You can only process domains where the identifier is not empty.
     */
    public function testDumpingDomainWithMissingDomainIdentifier() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain->setOrganization($organization);
        $this->manager->dumpDomainToConfig($domain, false);
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\IOException
     * @expectedExceptionMessageRegExp /^Failed to load content from file/
     */
    public function testParsingDomainFromMissingConfigFile() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');

        $this->manager->updateDomainFromConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException
     * @expectedExceptionMessage The domain configuration identifier does not match with the filename.
     */
    public function testParsingDomainWithWrongDomainIdentifier() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');

        // First create a domain config .json file in the correct location
        $this->fileSystem->dumpFile($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json', '{
            "title": "Foo",
            "identifier": "foo"
        }');

        $this->manager->updateDomainFromConfig($domain);
    }

    public function testParsingDomain() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');

        // First create a domain config .json file in the correct location
        $this->fileSystem->dumpFile($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json', $this->exampleConfig);

        // Then update domain from config.
        $this->manager->updateDomainFromConfig($domain);

        // Make sure, that domain now reflects the config .json file
        $this->assertEquals('My test Domain', $domain->getTitle());
        $this->assertEquals('my_test_domain', $domain->getIdentifier());
        $this->assertEquals(["view domain" => "true", "update domain" => "true"], $domain->getPermissions());

        $this->assertCount(2, $domain->getContentTypes());

        /**
         * @var ContentType $ct
         */
        $ct = $domain->getContentTypes()->first();
        $this->assertEquals('First Content Type', $ct->getTitle());
        $this->assertEquals('first_ct', $ct->getIdentifier());
        $this->assertEquals(new FieldablePreview("https://example.com", "query { type }"), $ct->getPreview());
        $this->assertCount(3, $ct->getFields());
        $this->assertCount(2, $domain->getSettingTypes());
        $this->assertCount(1, $domain->getDomainMemberTypes());
    }

    public function testDumpingDomainConfig() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->exampleConfig);
        $domain
            ->setOrganization($organization)
            ->setId('XXX-YYY-ZZZ');

        // First create a domain config .json file to test force ovrrride.
        $this->fileSystem->dumpFile($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json', 'foo');
        $this->assertFalse($this->manager->dumpDomainToConfig($domain));
        $this->assertEquals('foo', file_get_contents($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json'));
        $this->assertTrue($this->manager->dumpDomainToConfig($domain, true));
        $this->assertJsonStringEqualsJsonString(static::$container->get('unite.cms.domain_definition_parser')->serialize($domain), file_get_contents($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json'));
    }
}