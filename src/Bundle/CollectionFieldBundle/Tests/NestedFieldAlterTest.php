<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 15:41
 */

namespace UniteCMS\CollectionFieldBundle\Tests\Field;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use UniteCMS\CollectionFieldBundle\Field\Types\CollectionFieldType;
use UniteCMS\CollectionFieldBundle\SchemaType\Factories\CollectionFieldTypeFactory;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\Types\Factories\TableViewConfigurationFactory;

class NestedFieldAlterTest extends TestCase
{
    public function testNestedFieldAlterTest() {

        $manager = new FieldTypeManager();

        $manager->registerFieldType(
            new CollectionFieldType(
                $this->createMock(CollectionFieldTypeFactory::class),
                $manager,
                new TableViewConfigurationFactory(100)
            )
        );
        $manager->registerFieldType(new class extends FieldType {
            const TYPE = 't_f1';
            public function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData) { $data[$field->getIdentifier()] = 'FOO'; }
        });
        $manager->registerFieldType(new class extends FieldType { const TYPE = 't_f2'; });

        $field = new ContentTypeField();
        $field->setType(CollectionFieldType::getType())->setIdentifier('collection');
        $field->setSettings(new FieldableFieldSettings([
            'fields' => [
                [
                    'type' => 't_f1',
                    'identifier' => 'f1',
                    'title' => 'F1',
                ],
                [
                    'type' => 't_f2',
                    'identifier' => 'f2',
                    'title' => 'F2',
                ]
            ]
        ]));
        $contentType = new ContentType();
        $contentType->addField($field);

        $data = [];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([], $data);

        $data = [
            'collection' => []
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'collection' => [],
        ], $data);

        $data = [
            'collection' => [
                [],
                []
            ],
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'collection' => [
                [
                    'f1' => 'FOO',
                ],
                [
                    'f1' => 'FOO',
                ],
            ]
        ], $data);

        $data = [
            'collection' => [
                [
                    'f1' => 'BAA',
                    'f2' => 'BAA',
                ],
                [
                    'f1' => 'BAA',
                    'f2' => 'BAA',
                ],
            ],
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'collection' => [
                [
                    'f1' => 'FOO',
                    'f2' => 'BAA',
                ],
                [
                    'f1' => 'FOO',
                    'f2' => 'BAA',
                ],
            ],
        ], $data);
    }
}
