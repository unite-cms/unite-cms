<?php

namespace UnitedCMS\CoreBundle\Tests\View;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitedCMS\CoreBundle\View\ViewType;
use UnitedCMS\CoreBundle\View\ViewTypeInterface;
use UnitedCMS\CoreBundle\View\ViewTypeManager;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;

class ViewTypeManagerTest extends TestCase
{

    public function testRegisterViews() {

        $view = new class extends ViewType{
            const TYPE = "test_register_view_test_type";
            function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array {
                return [
                    'foo' => 'baa',
                ];
            }
        };

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturn('mocked_url');

        $manager = new ViewTypeManager($urlGenerator);
        $manager->registerViewType($view);


        // Check that the view was registered.
        $this->assertEquals($view, $manager->getViewType('test_register_view_test_type'));

        // Check get template render parameter.
        $view = new View();
        $view
            ->setType('table')
            ->setTitle('New View')
            ->setIdentifier('new_view')
            ->setContentType(new ContentType())
            ->getContentType()
                ->setTitle('ct')
                ->setIdentifier('ct')
                ->setDomain(new Domain())
                ->getDomain()
                    ->setTitle('D1')
                    ->setIdentifier('d1')
                    ->setOrganization(new Organization())
                    ->getOrganization()
                        ->setTitle('O1')
                        ->setIdentifier('o1');

        $view->setType('test_register_view_test_type');

        $parameters = $manager->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE);

        $this->assertTrue($parameters->isSelectModeSingle());
        $this->assertEquals('baa', $parameters->get('foo'));


    }
}