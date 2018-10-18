<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 18.10.18
 * Time: 09:21
 */

namespace UniteCMS\CoreBundle\Tests\DependencyInjection;


use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DependencyInjectionTest extends KernelTestCase
{
    public function testDefaultDomainConfigDirInjection() {

        // Test (empty) default configuration.
        $kernel = static::bootKernel(['environment' => 'dev']);

        $this->assertEquals(
            $kernel->getContainer()->getParameter('kernel.project_dir').'/config/unite/',
            $kernel->getContainer()->get('unite.cms.domain_config_manager')->getDomainConfigDir()
        );

    }

    public function testOverrideDomainConfigDirInjection() {

        // Test (overridden) test configuration.
        $kernel = static::bootKernel(['environment' => 'test']);

        $this->assertEquals(
            $kernel->getContainer()->getParameter('kernel.cache_dir').'/unite/config/',
            $kernel->getContainer()->get('unite.cms.domain_config_manager')->getDomainConfigDir()
        );
    }
}