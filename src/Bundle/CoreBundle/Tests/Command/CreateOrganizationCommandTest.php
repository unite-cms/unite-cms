<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 20.10.17
 * Time: 15:12
 */

namespace UnitedCMS\CoreBundle\Tests\Command;


use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UnitedCMS\CoreBundle\Command\CreateOrganizationCommand;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreateOrganizationCommandTest extends DatabaseAwareTestCase
{
    public function testCreateOrganizationCommand() {

        $application = new Application(self::$kernel);
        $application->add(new CreateOrganizationCommand(
            $this->container->get('doctrine.orm.default_entity_manager'),
            $this->container->get('validator'),
            $this->container->get('united.cms.domain_definition_parser')
        ));

        $command = $application->find('united:organization:create');
        $commandTester = new CommandTester($command);

        $this->assertCount(0, $this->em->getRepository('UnitedCMSCoreBundle:Organization')->findAll());

        $title = 'My new created Organization';
        $identifier = 'my_new_created_organization';

        $commandTester->setInputs(array($title, '', 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Organization was created successfully!', $commandTester->getDisplay());

        // Verify creation
        $organizations = $this->em->getRepository('UnitedCMSCoreBundle:Organization')->findAll();
        $this->assertCount(1, $organizations);
        $this->assertEquals($title, $organizations[0]->getTitle());
        $this->assertEquals($identifier, $organizations[0]->getIdentifier());

        // Now let's try to create another organization with the same identifier.
        $commandTester->setInputs(array($title, '', 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the organization', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UnitedCMSCoreBundle:Organization')->findAll());
    }
}