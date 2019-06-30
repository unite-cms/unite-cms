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
use UniteCMS\CoreBundle\Command\ListDomainCommand;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ListDomainCommandTest extends DatabaseAwareTestCase
{
    public function testListDomainCommand() {

        $application = new Application(self::$kernel);
        $application->add(new ListDomainCommand(
            static::$container->get('doctrine.orm.default_entity_manager'),
            static::$container->get('unite.cms.domain_config_manager')
        ));

        $command = $application->find('unite:domain:list');
        $commandTester = new CommandTester($command);

        $organization = new Organization();
        $organization->setIdentifier('org')->setTitle('Org');

        $this->em->persist($organization);
        $this->em->flush();

        $domain1 = new Domain();
        $domain1->setTitle('Domain 1')->setIdentifier('domain1')->setOrganization($organization);

        $domain2 = new Domain();
        $domain2->setTitle('Domain 2')->setIdentifier('domain2')->setOrganization($organization);

        $domain3 = new Domain();
        $domain3->setTitle('Domain 3')->setIdentifier('domain3')->setOrganization($organization);

        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($domain3);
        $this->em->flush();

        $filesystem = static::$container->get('filesystem');
        $manager = static::$container->get('unite.cms.domain_config_manager');

        $filesystem->remove($manager->getOrganizationConfigPath($organization) . 'domain3.json');
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain4.json', '{ "title": "Domain 4", "identifier": "domain4" }');
        $filesystem->dumpFile($manager->getOrganizationConfigPath($organization) . 'domain5.json', '{ "title": "Domain 5", "identifier": "domain5" }');

        $commandTester->execute(array(
            'command' => $command->getName(),
            'organization' => $organization->getIdentifier()
        ));

        $this->assertEquals("+------------+----------+------------+-----------------------+
| Identifier | Title    | Persisted? | Config in filesystem? |
+------------+----------+------------+-----------------------+
| domain1    | Domain 1 | Yes        | Yes                   |
| domain2    | Domain 2 | Yes        | Yes                   |
| domain3    | Domain 3 | Yes        | No                    |
| domain4    |          | No         | Yes                   |
| domain5    |          | No         | Yes                   |
+------------+----------+------------+-----------------------+
", $commandTester->getDisplay());
    }
}
