<?php

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class TableViewTypeTest extends DatabaseAwareTestCase
{

    public function testTableViewWithoutSettings()
    {

        // Create TableView instance.
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

        // View should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = $this->container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'created' => 'Created',
                'updated' => 'Updated',
                'id' => 'ID',
            ],
            $parameters->get('columns')
        );
        $this->assertEquals(
            [
                'field' => 'updated',
                'asc' => false,
            ],
            $parameters->get('sort')
        );
    }

    public function testTableViewWithInvalidSettings()
    {

        // Create TableView instance.
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

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('f1')->setTitle('F1');
        $view->getContentType()->addField($field);

        $view->setSettings(
            new ViewSettings(
                [
                    'foo' => 'baa',
                ]
            )
        );

        // View should not be valid.
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $view->setSettings(new ViewSettings([]));

        // Test validating invalid columns.
        $view->setSettings(new ViewSettings(['columns' => 'string']));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.columns', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['columns' => ['foo' => 'Foo', 'baa' => 'Baa']]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(2, $errors);
        $this->assertEquals('unknown_column', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.columns.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('unknown_column', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('settings.columns.baa', $errors->get(1)->getPropertyPath());

        // Test validating invalid sort_field.
        $view->setSettings(new ViewSettings(['sort_field' => ['foo']]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_field', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_field' => 'foo']));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('unknown_column', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_field', $errors->get(0)->getPropertyPath());

        // Test validating invalid sort_asc.
        $view->setSettings(new ViewSettings(['sort_asc' => 'yes']));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_asc', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_asc' => 'foo']));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_asc', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_asc' => ['foo']]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.sort_asc', $errors->get(0)->getPropertyPath());

        // Test validating invalid filter.
        $view->setSettings(new ViewSettings(['filter' => ['foo']]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.filter', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => 'string']));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.filter', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => ['AND' => ['foo']]]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.filter', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => ['operator' => 'foo', 'field' => 'foo', 'value' => 'baa']]));
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.filter', $errors->get(0)->getPropertyPath());

        $view->setSettings(
            new ViewSettings(['filter' => ['operator' => '=', 'field' => 'f1', 'value' => 'baa', 'foo' => 'baa']])
        );
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('wrong_setting_definition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.filter', $errors->get(0)->getPropertyPath());
    }

    public function testTableViewWithValidSettings()
    {

        // Create TableView instance.
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

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('f1')->setTitle('F1');
        $view->getContentType()->addField($field);

        $filter = [
            'AND' => [
                [
                    'OR' => [
                        ['field' => 'f1', 'operator' => 'LIKE', 'value' => 'Foo'],
                        ['field' => 'f1', 'operator' => 'LIKE', 'value' => 'Baa'],
                    ],
                ],
                ['field' => 'id', 'operator' => '=', 'value' => 'XXX-XXX-XXX'],
            ],
        ];

        $view->setSettings(
            new ViewSettings(
                [
                    'columns' => [
                        'f1' => 'Title',
                        'id' => 'baa',
                        'f1.any_sub' => 'baa',
                    ],
                    'filter' => $filter,
                    'sort_field' => 'f1',
                    'sort_asc' => true,
                ]
            )
        );

        // View should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = $this->container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'f1' => 'Title',
                'id' => 'baa',
                'f1.any_sub' => 'baa',
            ],
            $parameters->get('columns')
        );
        $this->assertEquals(
            [
                'field' => 'f1',
                'asc' => true,
            ],
            $parameters->get('sort')
        );
        $this->assertEquals($filter, $parameters->get('filter'));
    }
}
