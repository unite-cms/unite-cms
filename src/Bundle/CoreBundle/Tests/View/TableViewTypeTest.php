<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.10.18
 * Time: 14:09
 */

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;
use UniteCMS\CoreBundle\View\ViewSettings;

class TableViewTypeTest extends ContainerAwareTestCase
{
    /**
     * @var View $view
     */
    private $view;

    /**
     * @var ViewSettings $viewSettings
     */
    private $viewSettings;

    public function setUp()
    {
        parent::setUp();
        $this->viewSettings = new ViewSettings();
        $this->view = new View();
        $this->view
            ->setType('table')
            ->setTitle('New View')
            ->setIdentifier('new_view')
            ->setSettings($this->viewSettings)
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
    }


    public function testTableView()
    {
        $this->viewSettings->sort = ['field' => 'created', 'asc' => true];

        // View should be valid.
        $this->assertCount(0, static::$container->get('validator')->validate($this->view));

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($this->view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'created' => [
                    'label' => 'Created',
                    'type' => 'date',
                ],
                'updated' => [
                    'label' => 'Updated',
                    'type' => 'date',
                ],
                'id' => [
                    'label' => 'Id',
                    'type' => 'id',
                ],
            ],
            $parameters->get('fields')
        );
        $this->assertEquals(
            [
                'field' => 'created',
                'asc' => true,
            ],
            $parameters->get('sort')
        );
        $this->assertEmpty($parameters->get('filter'));
    }

    public function testFieldAddConfiguration() {

        $field = new ContentTypeField();
        $field
            ->setIdentifier('test')
            ->setTitle('Test')
            ->setType('state')
            ->setSettings(new FieldableFieldSettings([
                'initial_place' => 'foo',
                'transitions' => [
                    'test' => [ 'label' => 'Test', 'from' => ['foo'], 'to' => 'baa' ],
                ],
                'places' => [
                    'foo' => [ 'label' => 'Foo' ],
                    'baa' => [ 'label' => 'Baa' ],
                ],
            ]));
        $this->view->getContentType()->addField($field);
        $this->viewSettings->fields = ['test'];

        // View should be valid.
        echo static::$container->get('validator')->validate($this->view);
        $this->assertCount(0, static::$container->get('validator')->validate($this->view));

        // Parameters should include state field config and assets
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($this->view);

        $this->assertEquals(['places' => $field->getSettings()->places], $parameters->getSettings()['fields']['test']['config']);
    }

}