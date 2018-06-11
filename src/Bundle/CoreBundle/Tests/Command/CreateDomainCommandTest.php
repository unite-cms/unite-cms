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
    private $validDomain = '{ "title": "Test controller access check domain", "identifier": "access-check", "content_types": [{"title": "CT 1", "identifier": "ct1"}], "setting_types": [{"title": "ST 1", "identifier": "st1"}] }';

    public function testCreateOrganizationCommand() {

        $application = new Application(self::$kernel);
        $application->add(new CreateDomainCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('validator'),
            static::$container->get('unite.cms.domain_definition_parser')
        ));

        $command = $application->find('unite:domain:create');
        $commandTester = new CommandTester($command);

        $organization = new Organization();
        $organization->setIdentifier('org')->setTitle('Org');

        $this->em->persist($organization);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll());

        $inputDomain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->validDomain);
        $commandTester->setInputs(array('0', $this->validDomain, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));

        // Verify output
        $this->assertContains('Domain was created successfully!', $commandTester->getDisplay());

        // Verify creation
        $domains = $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll();
        $this->assertCount(1, $domains);
        $this->assertEquals($inputDomain->getTitle(), $domains[0]->getTitle());
        $this->assertEquals($inputDomain->getIdentifier(), $domains[0]->getIdentifier());
        $this->assertEquals($organization, $domains[0]->getOrganization());


        // Now let's try to create another domain with the same identifier.
        $commandTester->setInputs(array('0', $this->validDomain, 'Y'));
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertContains('There was an error while creating the domain', $commandTester->getDisplay());
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Domain')->findAll());
    }
}
