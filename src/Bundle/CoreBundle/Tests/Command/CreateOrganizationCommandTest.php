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
use UniteCMS\CoreBundle\Command\CreateOrganizationCommand;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreateOrganizationCommandTest extends DatabaseAwareTestCase
{
    public function testCreateOrganizationCommand() {

        $application = new Application(self::$kernel);
        $application->add(new CreateOrganizationCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator')
        ));

        $command = $application->find('unite:organization:create');
        $commandTester = new CommandTester($command);

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll());

        $title = 'My new created Organization';
        $identifier = 'my_new_created_organization';

        $commandTester->setInputs(array($title, '', 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Organization was created successfully!', $commandTester->getDisplay());

        // Verify creation
        $organizations = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll();
        $this->assertCount(1, $organizations);
        $this->assertEquals($title, $organizations[0]->getTitle());
        $this->assertEquals($identifier, $organizations[0]->getIdentifier());

        // Now let's try to create another organization with the same identifier.
        $commandTester->setInputs(array($title, '', 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the organization', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll());
    }

    public function testCreateOrganizationWithExistingConfigCommand() {

        // Create organization folder
        $filesystem = static::$container->get('filesystem');
        $manager = static::$container->get('unite.cms.domain_config_manager');
        $domain_config = '{ "title": "Existing D", "identifier": "existing_d" }';
        $filesystem->dumpFile($manager->getDomainConfigDir() . 'existing_o/existing_d.json', $domain_config);

        $application = new Application(self::$kernel);
        $application->add(new CreateOrganizationCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator')
        ));

        $command = $application->find('unite:organization:create');
        $commandTester = new CommandTester($command);

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll());

        $commandTester->setInputs(array('Existing O', 'existing_o', 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Organization was created successfully!', $commandTester->getDisplay());
        $this->assertStringEqualsFile($manager->getDomainConfigDir() . 'existing_o/existing_d.json', $domain_config);
    }
}
