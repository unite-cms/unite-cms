<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 13:09
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Service\UniteRouter;

class UniteUrlGeneratorTest extends KernelTestCase
{
    /**
     * @var Router $router
     */
    private $router;

    public function setUp()
    {
        self::bootKernel();
        $this->router = self::$kernel->getContainer()->get('router');
    }

    public function testServiceDecorator() {
        $this->assertInstanceOf(UniteRouter::class, $this->router);
    }

    public function testGeneratingOrganizationUrls() {

        $organization = new Organization();
        $organization->setIdentifier('org1_org1');

        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1']),
            $this->router->generate('unitecms_core_domain_index', $organization)
        );
    }
}