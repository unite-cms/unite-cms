<?php

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

/**
 * @deprecated 1.0 this tests are written for the old sortable view implementation. As we are backward compatible, they
 * must pass, however we will remove them before 1.0 release. Please use table view with sortable option instead!
 */
class OldSortableTableViewTypeTest extends ContainerAwareTestCase
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
            ->setIdentifier('new_view')
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
        $field->setType('sortindex')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        return $view;
    }

    public function testSortableViewWithPositionSetting()
    {
        $view = $this->createInstance();

        // View should be valid. Only deprecation warnings allowed.
        foreach(static::$container->get('validator')->validate($view) as $error) {
            $this->assertTrue(!isset($error->getConstraint()->payload['severity']) || $error->getConstraint()->payload['severity'] === 'warning');
        }


        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'position' => [
                    'label' => 'Position',
                    'type' => 'sortindex',
                ],
                'id' => [
                    'label' => 'Id',
                    'type' => 'id',
                ],
                'created' => [
                    'label' => 'Created',
                    'type' => 'date',
                ],
                'updated' => [
                    'label' => 'Updated',
                    'type' => 'date',
                ],
            ],
            $parameters->get('fields')
        );
        $this->assertEquals('position', $parameters->get('sort')['field']);
        $this->assertEquals(true, $parameters->get('sort')['sortable']);
        $this->assertEquals(true, $parameters->get('sort')['asc']);
    }

    public function testSortableViewWithInvalidSettings()
    {
        $view = $this->createInstance();
        $view->setSettings(new ViewSettings());

        // View settings all optional
        $this->assertCount(0, static::$container->get('validator')->validate($view));

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
        $this->assertEquals('Unrecognized option "foo" under "settings"', $errors->get(0)->getMessageTemplate());

        // Test validating invalid columns.
        $view->setSettings(new ViewSettings(['columns' => 'string', 'sort_field' => 'position']));
        $errors = static::$container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.fields". Expected array, but got string', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());

        $view->setSettings(
            new ViewSettings(['columns' => ['foo' => 'Foo'], 'sort_field' => 'position'])
        );
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
    }

    public function testSortableViewWithValidSettings()
    {
        $view = $this->createInstance();
        $view->setSettings(
            new ViewSettings(
                [
                    'columns' => [
                        'id' => 'ID',
                        'position.any_sub' => [
                            'label' => 'Baa',
                            'type' => 'text',
                        ],
                    ],
                    'sort_field' => 'position',
                ]
            )
        );

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        // View should be valid. Only deprecation warnings allowed.
        foreach(static::$container->get('validator')->validate($view) as $error) {
            $this->assertTrue(!isset($error->getConstraint()->payload['severity']) || $error->getConstraint()->payload['severity'] === 'warning');
        }

        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals(
            [
                'id' => [
                    'label' => 'ID',
                    'type' => 'id',
                ],
                'position' => [
                    'label' => 'Baa',
                    'type' => 'sortindex',
                    'settings' => [
                        'fields' => [
                            'any_sub' => [
                                'type' => 'text',
                            ]
                        ],
                    ]
                ],
            ],
            $parameters->get('fields')
        );
        $this->assertEquals('position', $parameters->get('sort')['field']);
    }
}
