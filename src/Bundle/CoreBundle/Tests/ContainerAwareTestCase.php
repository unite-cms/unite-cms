<?php

namespace UniteCMS\CoreBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ContainerAwareTestCase extends KernelTestCase
{

    /**
     * @var ContainerInterface $container
     */
    protected $container;

    protected function generateRandomMachineName($count = 0)
    {
        $allowed = str_split('abcdefghijklmnopqrstuvwxyz0123456789_');
        $return = '';
        for ($i = 0; $i < $count; $i++) {
            $return .= $allowed[random_int(0, count($allowed) - 1)];
        }

        return $return;
    }

    protected function generateRandomUTF8String($count = 0)
    {
        return substr(base64_encode(random_bytes($count)), 0, $count);
    }

    public function setUp()
    {
        $kernel = static::bootKernel(['debug' => false]);
        $this->container = $kernel->getContainer();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->container = null;
    }
}
