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
use UniteCMS\CoreBundle\Command\CreatePlatformAdminCommand;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreatePlatformAdminCommandTest extends DatabaseAwareTestCase
{
    public function testCreateOrganizationCommand() {

        $application = new Application(self::$kernel);
        $command = new CreatePlatformAdminCommand(
            $this->container->get('doctrine.orm.default_entity_manager'),
            $this->container->get('validator'),
            $this->container->get('security.password_encoder')
        );
        $command->disableHidePasswordInput();
        $application->add($command);

        $command = $application->find('unite:user:create');
        $commandTester = new CommandTester($command);

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:User')->findAll());

        $name = $this->generateRandomMachineName(10);
        $email = $this->generateRandomMachineName(10) . '@' . $this->generateRandomMachineName(10) . '.com';
        $password = $this->generateRandomMachineName(10);

        $commandTester->setInputs(array($name, $email, $password, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Platform Admin was created!', $commandTester->getDisplay());

        // Verify creation
        $users = $this->em->getRepository('UniteCMSCoreBundle:User')->findAll();
        $this->assertCount(1, $users);
        $this->assertEquals($name, $users[0]->getName());
        $this->assertEquals($email, $users[0]->getEmail());
        $this->assertTrue($this->container->get('security.password_encoder')->isPasswordValid($users[0], $password));
        $this->assertContains(User::ROLE_PLATFORM_ADMIN, $users[0]->getRoles());


        // Now let's try to create another user with the same email.
        $commandTester->setInputs(array($name, $email, $password, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the user', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:User')->findAll());
    }
}
