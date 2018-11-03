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
use UniteCMS\CoreBundle\View\Types\GridViewConfiguration;
use UniteCMS\CoreBundle\View\Types\TableViewConfiguration;

class GridViewConfigurationTest extends TestCase
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

        $this->configuration = new GridViewConfiguration($this->view->getContentType(), $this->fieldTypeManager);
        $this->processor = new Processor();
    }

    public function testEmptyConfig()
    {
        $config = $this->processor->processConfiguration($this->configuration, ['settings' => []]);
        $this->assertEquals([
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => 'Title',
                ],
                'updated' => [
                    'meta' => true,
                    'type' => 'date',
                    'label' => 'Updated',
                ]
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
        ], $config);

        $this->view->getContentType()->getFields()->removeElement($this->view->getContentType()->getFields()->first());

        $config = $this->processor->processConfiguration($this->configuration, ['settings' => []]);
        $this->assertEquals([
            'fields' => [
                'id' => [
                    'type' => 'id',
                    'label' => 'Id',
                ],
                'updated' => [
                    'meta' => true,
                    'type' => 'date',
                    'label' => 'Updated',
                ]
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
        ], $config);
    }

    public function testSettingFields()
    {
        $config = $this->processor->processConfiguration($this->configuration, ['settings' => [
            'fields' => ['id', 'title', 'updated' => ['meta' => true]]
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
                    'meta' => true,
                ],
            ],
            'sort' => [
                'field' => 'updated',
                'asc' => false,
            ],
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
        ], $config);
    }
}