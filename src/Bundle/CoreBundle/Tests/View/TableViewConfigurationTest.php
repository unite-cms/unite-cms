<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.10.18
 * Time: 14:33
 */

namespace UniteCMS\CoreBundle\Tests\View;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\Processor;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\Types\TextAreaFieldType;
use UniteCMS\CoreBundle\Field\Types\TextFieldType;
use UniteCMS\CoreBundle\View\Types\TableViewConfiguration;

class TableViewConfigurationTest extends TestCase
{
    /**
     * @var View $view
     */
    private $view;

    /**
     * @var TableViewConfiguration $configuration
     */
    private $configuration;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * @var Processor $processor
     */
    private $processor;

    public function setUp()
    {
        $this->view = new View();
        $title = new ContentTypeField();
        $title->setIdentifier('title')->setTitle('Title')->setType('text');
        $this->view->setContentType(new ContentType())->getContentType()->addField($title);
        $this->fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $this->fieldTypeManager->expects($this->any())->method('getFieldType')->will($this->returnValueMap([
            ['text', new TextFieldType()],
            ['textarea', new TextAreaFieldType()],
        ]));

        $this->configuration = new TableViewConfiguration($this->view->getContentType(), $this->fieldTypeManager);
        $this->processor = new Processor();
    }

    public function testEmptyConfig()
    {
        $config = $this->processor->processConfiguration($this->configuration, ['settings' => []]);
        $this->assertEquals([
            'fields' => [
                'id' => [
                    'type' => 'id',
                    'label' => 'Id',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
                'created' => [
                    'type' => 'date',
                    'label' => 'Created',
                ],
                'updated' => [
                    'type' => 'date',
                    'label' => 'Updated',
                ]
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
            'actions' => []
        ], $config);
    }

    public function testSettingFields()
    {
        $config = $this->processor->processConfiguration($this->configuration, ['settings' => [
            'fields' => ['id', 'title', 'updated']
        ]]);
        $this->assertEquals([
            'fields' => [
                'id' => [
                    'type' => 'id',
                    'label' => 'Id',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
                'updated' => [
                    'type' => 'date',
                    'label' => 'Updated',
                ],
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
            'actions' => []
        ], $config);

        $config = $this->processor->processConfiguration($this->configuration, ['settings' => [
            'fields' => ['id' => 'Foo', 'title' => 'Baa', 'updated' => 'Any']
        ]]);
        $this->assertEquals([
            'fields' => [
                'id' => [
                    'type' => 'id',
                    'label' => 'Foo',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Baa',
                ],
                'updated' => [
                    'type' => 'date',
                    'label' => 'Any',
                ],
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
            'actions' => []
        ], $config);

        $config = $this->processor->processConfiguration($this->configuration, ['settings' => [
            'fields' => [
                'id' => [
                    'type' => 'text',
                    'label' => 'Foo',
                ],
                'title' => [
                    'type' => 'textarea',
                    'label' => 'Baa',
                ],
                'updated' => [
                    'type' => 'integer',
                    'label' => 'Any',
                ],
            ]
        ]]);
        $this->assertEquals([
            'fields' => [
                'id' => [
                    'type' => 'text',
                    'label' => 'Foo',
                ],
                'title' => [
                    'type' => 'textarea',
                    'label' => 'Baa',
                ],
                'updated' => [
                    'type' => 'integer',
                    'label' => 'Any',
                ],
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
            'actions' => []
        ], $config);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "foo" under "settings"
     */
    public function testInvalidSettingsKey()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['foo' => 'baa']]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "foo" under "settings.fields.id"
     */
    public function testInvalidFieldSettingsKey()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['fields' => ['id' => ['foo' => 'baa']]]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "foo" under "settings.sort"
     */
    public function testInvalidSortSettingsKey()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => ['foo' => 'baa']]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid filter configuration
     */
    public function testInvalidFilterSettingsKey()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['filter' => ['foo' => 'baa']]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unknown field "x"
     */
    public function testUnknownField()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['fields' => ['x']]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unknown field "x"
     */
    public function testUnknownSetSortField()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => ['field' => 'x']]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The child node "field" at path "settings.sort" must be configured.
     */
    public function testSortableWithoutSort()
    {
        $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => ['sortable' => true]]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid Action Url given!
     */
    public function testInvalidActionUrl()
    {
        $actions = [
           [
               'url' => true
           ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage No Action Url given!
     */
    public function testNoActionUrl()
    {
        $actions = [
           [
               'target' => '1212'
           ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid Action Target given, the allowed options are "_self" and "_target"!
     */
    public function testInvalidActionTarget()
    {
        $actions = [
           [
               'url' => 'http://www.orf.at',
               'target' => '1212'
           ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid Action Label given!
     */
    public function testInvalidActionLabel()
    {
        $actions = [
            [
                'url' => 'http://www.orf.at',
                'label' => true
            ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid Action Icon given!
     */
    public function testInvalidActionIcon()
    {
        $actions = [
           [
               'url' => 'http://www.orf.at',
               'icon' => 1212
           ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

     /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid Action Icon given!
     */
    public function testInvalidActionIconMultiple()
    {
        $actions = [
           [
               'url' => 'http://www.orf.at',
               'icon' => 'file'
           ],
           [
               'url' => 'http://www.orf.at',
               'icon' => 1212
           ]
        ];
        $this->processor->processConfiguration($this->configuration, ['settings' => ['actions' => $actions]]);
    }

    public function testSortableWithSort()
    {
        $config = $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => [
            'field' => 'title',
            'sortable' => true,
        ]]]);
        $this->assertEquals([
            'field' => 'title',
            'asc' => true,
            'sortable' => true,
        ], $config['sort']);

        $config = $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => [
            'field' => 'title',
            'asc' => false,
        ]]]);
        $this->assertEquals([
            'field' => 'title',
            'asc' => false,
        ], $config['sort']);

        $config = $this->processor->processConfiguration($this->configuration, ['settings' => ['sort' => [
            'field' => 'title',
            'asc' => false,
            'sortable' => true,
        ]]]);
        $this->assertEquals([
            'field' => 'title',
            'asc' => true,
            'sortable' => true,
        ], $config['sort']);
    }

}