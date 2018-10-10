<?php

namespace UniteCMS\CoreBundle\Tests\View;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\ViewType;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

class ViewTypeManagerTest extends TestCase
{

    public function testRegisterViews()
    {

        $view = new class extends ViewType
        {
            const TYPE = "test_register_view_test_type";

            function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
            {
                return [
                    'foo' => 'baa',
                ];
            }
        };

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturn('mocked_url');

        $manager = new ViewTypeManager($urlGenerator, $this->createMock(FieldTypeManager::class));
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
