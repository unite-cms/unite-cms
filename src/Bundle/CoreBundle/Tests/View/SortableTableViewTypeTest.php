<?php

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class SortableTableViewTypeTest extends DatabaseAwareTestCase
{

    /**
     * @return View
     */
    private function createInstance()
    {
        $view = new View();
        $view
            ->setType('sortable')
            ->setTitle('New View')
            ->setIdentifier('new-view')
            ->setSettings(new ViewSettings(['sort_field' => 'position']))
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

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        return $view;
    }

    public function testSortableViewWithPositionSetting()
    {
        $view = $this->createInstance();

        // View should be valid.
        $this->assertCount(0, static::$container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'created' => 'Created',
                'updated' => 'Updated',
                'id' => 'ID',
            ],
            $parameters->get('columns')
        );
        $this->assertEquals('position', $parameters->get('sort_field'));
    }

    public function testSortableViewWithInvalidSettings()
    {
        $view = $this->createInstance();
        $view->setSettings(new ViewSettings());

        // View should not be valid.
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $view->setSettings(
            new ViewSettings(
                [
                    'sort_field' => 'position',
                    'foo' => 'baa',
                ]
            )
        );

        // View should not be valid.
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // Test validating invalid columns.
        $view->setSettings(new ViewSettings(['columns' => 'string', 'sort_field' => 'position']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.columns', $errors->get(0)->getPropertyPath());

        $view->setSettings(
            new ViewSettings(['columns' => ['foo' => 'Foo', 'baa' => 'Baa'], 'sort_field' => 'position'])
        );
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(2, $errors);
        $this->assertEquals('unknown_column', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.columns.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('unknown_column', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('settings.columns.baa', $errors->get(1)->getPropertyPath());

        // Test validating invalid sort_field.
        $view->setSettings(new ViewSettings(['sort_field' => ['foo']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_field', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_field' => 'foo']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('unknown_column', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_field', $errors->get(0)->getPropertyPath());
    }

    public function testSortableViewWithValidSettings()
    {
        $view = $this->createInstance();
        $view->setSettings(
            new ViewSettings(
                [
                    'columns' => [
                        'id' => 'ID',
                        'position' => 'Position',
                        'position.any_sub' => 'baa',
                    ],
                    'sort_field' => 'position',
                ]
            )
        );

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        // View should be valid.
        $this->assertCount(0, static::$container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'id' => 'ID',
                'position' => 'Position',
                'position.any_sub' => 'baa',
            ],
            $parameters->get('columns')
        );
        $this->assertEquals('position', $parameters->get('sort_field'));
    }
}
