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
use UniteCMS\CoreBundle\Command\ImportDomainCommand;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ImportDomainCommandTest extends DatabaseAwareTestCase
{
    public function testAbortImportDomainCommand() {

        $application = new Application(self::$kernel);
        $application->add(new ImportDomainCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator'),
            static::$container->get('unite.cms.domain_config_manager')
        ));

        $command = $application->find('unite:domain:import');
        $commandTester = new CommandTester($command);

        $organization = new Organization();
        $organization->setIdentifier('org')->setTitle('Org');

        $this->em->persist($organization);
        $this->em->flush();

        $domain1 = new Domain();
        $domain1->setTitle('Domain 1')->setIdentifier('domain1')->setOrganization($organization);

        $this->em->persist($domain1);
        $this->em->flush();

        $filesystem = static::$container->get('filesystem');
        $manager = static::$container->get('unite.cms.domain_config_manager');
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain2.json', '{ "title": "Domain 2", "identifier": "domain2" }');

        // Try to import an existing domain config with changed title.
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain1.json', '{ "title": "Domain 1 - Updated", "identifier": "domain1" }');
        $commandTester->setInputs(['n']);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => 'org',
            'domain' => 'domain1'
        ));
        $this->assertContains('Title: Domain 1 - Updated', $commandTester->getDisplay());
        $this->assertContains('Identifier: domain1', $commandTester->getDisplay());
        $this->assertNotContains('Domain entry in database was updated successfully!', $commandTester->getDisplay());
        $this->assertEquals('Domain 1', $domain1->getTitle());


        // Try to import a new domain from existing config.
        $commandTester->setInputs(['n']);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => 'org',
            'domain' => 'domain2'
        ));
        $this->assertContains('Title: Domain 2', $commandTester->getDisplay());
        $this->assertContains('Identifier: domain2', $commandTester->getDisplay());
        $this->assertNotContains('Database entry was created successfully!', $commandTester->getDisplay());
        $this->assertNull($this->em->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(['organization' => $organization, 'identifier' => 'domain2']));
    }

    public function testImportDomainCommand() {

        $application = new Application(self::$kernel);
        $application->add(new ImportDomainCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator'),
            static::$container->get('unite.cms.domain_config_manager')
        ));

        $command = $application->find('unite:domain:import');
        $commandTester = new CommandTester($command);

        $organization = new Organization();
        $organization->setIdentifier('org')->setTitle('Org');

        $this->em->persist($organization);
        $this->em->flush();

        $domain1 = new Domain();
        $domain1->setTitle('Domain 1')->setIdentifier('domain1')->setOrganization($organization);

        $this->em->persist($domain1);
        $this->em->flush();

        $filesystem = static::$container->get('filesystem');
        $manager = static::$container->get('unite.cms.domain_config_manager');
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain2.json', '{ "title": "Domain 2", "identifier": "domain2" }');


        // Try to import an existing domain config without any updates.
        $commandTester->setInputs(['Y']);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => 'org',
            'domain' => 'domain1',
        ));
        $this->assertContains('Title: Domain 1', $commandTester->getDisplay());
        $this->assertContains('Identifier: domain1', $commandTester->getDisplay());
        $this->assertContains('Domain entry in database was updated successfully!', $commandTester->getDisplay());

        // Try to import an existing domain config with changed title.
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain1.json', '{ "title": "Domain 1 - Updated", "identifier": "domain1" }');
        $commandTester->setInputs(['Y']);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => 'org',
            'domain' => 'domain1'
        ));
        $this->assertContains('Title: Domain 1 - Updated', $commandTester->getDisplay());
        $this->assertContains('Identifier: domain1', $commandTester->getDisplay());
        $this->assertContains('Domain entry in database was updated successfully!', $commandTester->getDisplay());
        $this->assertEquals('Domain 1 - Updated', $domain1->getTitle());


        // Try to import a new domain from existing config.
        $commandTester->setInputs(['Y']);
        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => 'org',
            'domain' => 'domain2'
        ));
        $this->assertContains('Title: Domain 2', $commandTester->getDisplay());
        $this->assertContains('Identifier: domain2', $commandTester->getDisplay());
        $this->assertContains('Database entry was created successfully!', $commandTester->getDisplay());
        $domain2 = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(['organization' => $organization, 'identifier' => 'domain2']);
        $this->assertEquals('Domain 2', $domain2->getTitle());
    }
}
