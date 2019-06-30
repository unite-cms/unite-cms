<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 20.10.17
 * Time: 15:12
 */

namespace UniteCMS\CoreBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UniteCMS\CoreBundle\Command\CreateDomainCommand;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreateDomainCommandTest extends DatabaseAwareTestCase
{
    private $validDomain = '{ "title": "@title", "identifier": "@identifier", "variables": { "@title": "Test controller access check domain", "@identifier": "access_check" }, "content_types": [{"title": "CT 1", "identifier": "ct1"}], "setting_types": [{"title": "ST 1", "identifier": "st1"}] }';

    public function testCreateDomainCommand() {

        $application = new Application(self::$kernel);
        $application->add(new CreateDomainCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator'),
            static::$container->get('unite.cms.domain_config_manager')
        ));

        $command = $application->find('unite:domain:create');
        $commandTester = new CommandTester($command);

        $organization = new Organization();
        $organization->setIdentifier('org')->setTitle('Org');

        $this->em->persist($organization);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll());

        $inputDomain = static::$container->get('unite.cms.domain_config_manager')->parse($this->validDomain);
        $commandTester->setInputs(array('0', 'access_check', $this->validDomain, null, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Database entry was created successfully!', $commandTester->getDisplay());

        // Verify creation
        $domains = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll();
        $this->assertCount(1, $domains);
        $this->assertEquals($inputDomain->getTitle(), $domains[0]->getTitle());
        $this->assertEquals($inputDomain->getIdentifier(), $domains[0]->getIdentifier());
        $this->assertEquals($organization, $domains[0]->getOrganization());

        $this->assertFileExists(static::$container->get('unite.cms.domain_config_manager')->getDomainConfigPath($domains[0]));
        static::$container->get('unite.cms.domain_config_manager')->loadConfig($domains[0]);
        $originalConfig = $domains[0]->getConfig();
        $this->assertNotEmpty($originalConfig);
        $this->assertJsonStringEqualsJsonString($this->validDomain, $originalConfig);

        // Now let's try to create another domain with the same identifier.
        $commandTester->setInputs(array('0', 'access_check', $this->validDomain, null, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the domain', $commandTester->getDisplay());

        // Make sure, that the access_check domain was not saved because there was an error.
        $domains = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll();
        $this->assertCount(1, $domains);
        $this->assertEquals($inputDomain->getTitle(), $domains[0]->getTitle());
        $this->assertEquals($inputDomain->getIdentifier(), $domains[0]->getIdentifier());
        $this->assertEquals($organization, $domains[0]->getOrganization());

        static::$container->get('unite.cms.domain_config_manager')->loadConfig($domains[0]);
        $this->assertEquals($originalConfig, $domains[0]->getConfig());


        // Now let's try to create another domain with empty config
        $commandTester->setInputs(array('0', 'access_check', null, null, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the domain', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll());

        $commandTester->setInputs(array('0', 'other_check', null, null, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Database entry was created successfully!', $commandTester->getDisplay());

        // Verify creation
        $domains = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll();
        $this->assertCount(2, $domains);
        $this->assertEquals('Other check', $domains[1]->getTitle());
        $this->assertEquals('other_check', $domains[1]->getIdentifier());
        $this->assertEquals($organization, $domains[1]->getOrganization());

        $this->assertFileExists(static::$container->get('unite.cms.domain_config_manager')->getDomainConfigPath($domains[1]));
    }
}
