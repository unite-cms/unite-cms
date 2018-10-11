<?php

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @deprecated 1.0 this tests are written for the old table view implementation. As we are backward compatible, they
 * must pass, however we will remove them before 1.0 release.
 */
class OldTableViewTypeTest extends DatabaseAwareTestCase
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
        $this->assertCount(0, static::$container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
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
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Unrecognized option "foo" under "settings"', $errors->get(0)->getMessageTemplate());

        $view->setSettings(new ViewSettings([]));

        // Test validating invalid columns.
        $view->setSettings(new ViewSettings(['columns' => 'string']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.fields". Expected array, but got string', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['columns' => ['foo' => 'Foo', 'baa' => 'Baa']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Unknown field "foo"', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        // Test validating invalid sort_field.
        $view->setSettings(new ViewSettings(['sort_field' => ['foo']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.sort.field". Expected scalar, but got array.', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_field' => 'foo']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Unknown field "foo"', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        // Test validating invalid sort_asc.
        $view->setSettings(new ViewSettings(['sort_asc' => 'yes']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.sort.asc". Expected boolean, but got string.', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_asc' => 'foo']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.sort.asc". Expected boolean, but got string.', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['sort_asc' => ['foo']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.sort.asc". Expected boolean, but got array.', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        // Test validating invalid filter.
        $view->setSettings(new ViewSettings(['filter' => ['foo']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid filter configuration', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => 'string']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid filter configuration', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => ['AND' => ['foo']]]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid filter configuration', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(new ViewSettings(['filter' => ['operator' => 'foo', 'field' => 'foo', 'value' => 'baa']]));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid filter configuration', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(
            new ViewSettings(['filter' => ['operator' => '=', 'field' => 'f1', 'value' => 'baa', 'foo' => 'baa']])
        );
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid filter configuration', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());
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
                        'f1.any_sub' => [
                            'label' => 'Baa',
                            'type' => 'email',
                        ],
                    ],
                    'filter' => $filter,
                    'sort_field' => 'f1',
                    'sort_asc' => true,
                ]
            )
        );

        // View should be valid.
        echo static::$container->get('validator')->validate($view);
        $this->assertCount(0, static::$container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'f1' => [
                    'label' => 'Title',
                    'type' => 'text',
                ],
                'id' => [
                    'label' => 'baa',
                    'type' => 'id'
                ],
                'f1.any_sub' => [
                    'label' => 'Baa',
                    'type' => 'email',
                ],
            ],
            $parameters->get('fields')
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
