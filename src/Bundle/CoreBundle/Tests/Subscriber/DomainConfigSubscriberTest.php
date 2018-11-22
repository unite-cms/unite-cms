<?php

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DomainConfigSubscriberTest extends DatabaseAwareTestCase
{
    private function configExists(string $path) : bool {
        $filesystem = static::$container->get('filesystem');
        return $filesystem->exists(self::$container->get('unite.cms.domain_config_manager')->getDomainConfigDir() . $path);
    }

    public function testFileSystemConfigChangesOnEntityChange()
    {
        // On org create, the config organization folder should be created.
        $this->assertFalse($this->configExists('org'));
        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');
        $this->em->persist($org);
        $this->em->flush();
        $this->assertTrue($this->configExists('org'));

        // On domain create, the domain config should be created.
        $this->assertFalse($this->configExists('org/domain.json'));
        $domain = new Domain();
        $domain->setIdentifier('domain')->setTitle('Domain')->setOrganization($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->assertTrue($this->configExists('org/domain.json'));

        // On domain title update, nothing should happen.
        $this->assertTrue($this->configExists('org/domain.json'));
        $this->assertFalse($this->configExists('org/domain_updated.json'));
        $domain->setTitle('Domain Updated');
        $this->em->flush();
        $this->assertTrue($this->configExists('org/domain.json'));
        $this->assertFalse($this->configExists('org/domain_updated.json'));

        // On domain identifier update, the domain config file should be renamed.
        $this->assertTrue($this->configExists('org/domain.json'));
        $this->assertFalse($this->configExists('org/domain_updated.json'));
        $domain->setIdentifier('domain_updated');
        $this->em->flush();
        $this->assertTrue($this->configExists('org/domain_updated.json'));
        $this->assertFalse($this->configExists('org/domain.json'));

        // On org title update, nothing should happen.
        $this->assertTrue($this->configExists('org'));
        $this->assertTrue($this->configExists('org/domain_updated.json'));
        $org->setTitle('Org Updated');
        $this->em->flush();
        $this->assertTrue($this->configExists('org'));
        $this->assertTrue($this->configExists('org/domain_updated.json'));

        // On org identifier update, the org config folder should be renamed.
        $this->assertTrue($this->configExists('org'));
        $this->assertTrue($this->configExists('org/domain_updated.json'));
        $org->setIdentifier('org_updated');
        $this->em->flush();
        $this->assertFalse($this->configExists('org'));
        $this->assertTrue($this->configExists('org_updated'));
        $this->assertTrue($this->configExists('org_updated/domain_updated.json'));

        // On domain delete, the domain config should be deleted.
        $this->assertTrue($this->configExists('org_updated/domain_updated.json'));
        $this->em->remove($domain);
        $this->em->flush();
        $this->assertTrue($this->configExists('org_updated'));
        $this->assertFalse($this->configExists('org_updated/domain_updated.json'));

        // On org delete, all the folder with all domain configs should be deleted.
        $this->assertTrue($this->configExists('org_updated/'));
        $this->em->remove($org);
        $this->em->flush();
        $this->assertFalse($this->configExists('org_updated'));
    }
}
