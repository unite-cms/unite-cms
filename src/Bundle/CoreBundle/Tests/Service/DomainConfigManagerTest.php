<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.10.18
 * Time: 11:45
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldablePreview;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
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
        $this->manager->loadConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage Organization identifier is empty.
     */
    public function testParsingDomainWithMissingOrganizationIdentifier() {

        $organization = new Organization();
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');
        $this->manager->loadConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage Organization identifier contains invalid characters.
     */
    public function testParsingDomainWithOrganizationIdentifierWithOnlyInvalidChars() {

        $organization = new Organization();
        $organization->setIdentifier('../');
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');
        $this->manager->loadConfig($domain);
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
        $this->manager->loadConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingDomainException
     * @expectedExceptionMessage Domain identifier contains invalid characters.
     */
    public function testParsingDomainWithDomainIdentifierOnlyInvalidChars() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = new Domain();
        $domain->setIdentifier('../');
        $domain->setOrganization($organization);
        $this->manager->loadConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage You can only process domains where the organization is not empty.
     */
    public function testDumpingDomainWithMissingOrganization() {
        $domain = new Domain();
        $domain->setIdentifier('my_test_domain');
        $this->manager->updateConfig($domain, null,false);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage Organization identifier is empty.
     */
    public function testDumpingDomainWithMissingOrganizationIdentifier() {

        $organization = new Organization();
        $domain = new Domain();
        $domain
            ->setOrganization($organization)
            ->setIdentifier('my_test_domain');
        $this->manager->updateConfig($domain, null,false);
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
        $this->manager->updateConfig($domain, null,false);
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

        $this->manager->loadConfig($domain);
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException
     * @expectedExceptionMessage The domain configuration identifier "foo" does not match with the filename "my_test_domain.json".
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

        $this->manager->loadConfig($domain);
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
        $this->manager->loadConfig($domain, true);

        // Make sure, that domain now reflects the config .json file
        $this->assertEquals('My test Domain', $domain->getTitle());
        $this->assertEquals('my_test_domain', $domain->getIdentifier());
        $this->assertEquals(["view domain" => "true", "update domain" => "true"], $domain->getPermissions());
        $this->assertEquals($this->exampleConfig, $domain->getConfig());

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
        $this->assertFalse($this->manager->updateConfig($domain));
        $this->assertEquals('foo', file_get_contents($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json'));
        $this->assertTrue($this->manager->updateConfig($domain, null,true));
        $this->assertJsonStringEqualsJsonString(static::$container->get('unite.cms.domain_definition_parser')->serialize($domain), file_get_contents($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json'));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException
     * @expectedExceptionMessage Domain config does not match parsed domain.
     */
    public function testDumpingDomainConfigWithInvalidCustomConfig() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->exampleConfig);
        $domain
            ->setOrganization($organization)
            ->setId('XXX-YYY-ZZZ');

        $domain->setConfig(str_replace('First Content Type', 'Foo Content Type', $this->exampleConfig));

        // Try to dump a custom config that is different from domain.
        $this->manager->updateConfig($domain, null, true);
    }

    public function testDumpingDomainConfigWithValidCustomConfig() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->exampleConfig);
        $domain
            ->setOrganization($organization)
            ->setId('XXX-YYY-ZZZ');

        $customConfig = json_decode($this->exampleConfig);
        $customConfig->variables = ['@title' => 'First Content Type'];
        $customConfig->content_types[0]->title = '@title';

        $domain->setConfig(json_encode($customConfig));


        // Try to dump a custom config that is the same as domain.
        $this->assertTrue($this->manager->updateConfig($domain, null,true));
    }

    public function testRemoveDomainConfig() {

        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->exampleConfig);
        $domain
            ->setOrganization($organization)
            ->setId('XXX-YYY-ZZZ');

        // First create a domain config .json file to test force ovrrride.
        $this->fileSystem->dumpFile($this->manager->getDomainConfigDir() . $organization->getIdentifier() . '/' . $domain->getIdentifier() . '.json', 'foo');
        $this->assertTrue($this->manager->configExists($domain));
        $this->manager->removeConfig($domain);
        $this->assertFalse($this->manager->configExists($domain));
        $this->manager->removeConfig($domain);
        $this->assertFalse($this->manager->configExists($domain));
    }

    public function testListConfig() {
        $organization = new Organization();
        $organization->setIdentifier('my_test_organization');
        $this->assertEquals([], $this->manager->listConfig($organization));

        $domain1 = new Domain();
        $domain1->setIdentifier('domain1')->setTitle('Domain1')->setOrganization($organization);
        $this->manager->updateConfig($domain1);

        $this->assertEquals(['domain1'], $this->manager->listConfig($organization));

        $domain2 = new Domain();
        $domain2->setIdentifier('domain2')->setTitle('Domain2')->setOrganization($organization);
        $this->manager->updateConfig($domain2);

        $this->assertEquals(['domain1', 'domain2'], $this->manager->listConfig($organization));
    }

    public function invalidIdentifierChars() {
        return array_map(
            function($c){ return [$c]; },
            ["D", "A", ".", " ", "?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", "%", "+", chr(0)]
        );
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingOrganizationException
     * @expectedExceptionMessage Organization identifier contains invalid characters.
     * @dataProvider invalidIdentifierChars
     */
    public function testOrganizationContainsInvalidChars(string $invalid_char) {

        // If the domain gets validated, only a-z0-9_ chard are allowed, however the config manager will not validate
        // the domain. So we also need to remove them before doing anything on the filesystem.

        $domain = new Domain();
        $domain->setTitle('test');

        $organization = new Organization();
        $organization->setTitle('test');

        $organization->setIdentifier('oprefix_'.$invalid_char.'_osuffix');
        $domain->setIdentifier('dprefix_'.$invalid_char.'_dsuffix');

        $this->expectException(MissingOrganizationException::class);
        $this->expectExceptionMessage('Organization identifier contains invalid characters.');
        $this->assertEquals($this->manager->getDomainConfigDir() . '/oprefix__osuffix/', $this->manager->getOrganizationConfigPath($organization));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\MissingDomainException
     * @expectedExceptionMessage Domain identifier contains invalid characters.
     * @dataProvider invalidIdentifierChars
     */
    public function testDomainContainsInvalidChars(string $invalid_char) {

        // If the domain gets validated, only a-z0-9_ chard are allowed, however the config manager will not validate
        // the domain. So we also need to remove them before doing anything on the filesystem.

        $domain = new Domain();
        $domain->setTitle('test');

        $organization = new Organization();
        $organization->setTitle('test');
        $domain->setOrganization($organization);

        $domain->setIdentifier('dprefix_'.$invalid_char.'_dsuffix');
        $this->expectException(MissingDomainException::class);
        $this->expectExceptionMessage('Domain identifier contains invalid characters.');
        $this->assertEquals($this->manager->getDomainConfigDir() . '/oprefix__osuffix/dprefix__dsuffix.json', $this->manager->getDomainConfigPath($domain));
    }
}