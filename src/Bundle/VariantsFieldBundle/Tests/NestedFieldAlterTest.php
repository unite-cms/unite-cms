<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 15:41
 */

namespace UniteCMS\VariantsFieldBundle\Tests\Field;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\VariantsFieldBundle\Field\Types\VariantsFieldType;
use UniteCMS\VariantsFieldBundle\SchemaType\Factories\VariantFactory;

class NestedFieldAlterTest extends TestCase
{
    public function testNestedFieldAlterTest() {

        $manager = new FieldTypeManager();

        $manager->registerFieldType(new VariantsFieldType($manager, $this->createMock(VariantFactory::class)));
        $manager->registerFieldType(new class extends FieldType {
            const TYPE = 't_f1';
            public function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData) { $data[$field->getIdentifier()] = 'FOO'; }
        });
        $manager->registerFieldType(new class extends FieldType { const TYPE = 't_f2'; });

        $field = new ContentTypeField();
        $field->setType(VariantsFieldType::getType())->setIdentifier('variants');
        $field->setSettings(new FieldableFieldSettings([
            'variants' => [
                [
                    'title' => 'A',
                    'identifier' => 'a',
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
                ],
                [
                    'title' => 'B',
                    'identifier' => 'b',
                    'fields' => [
                        [
                            'type' => 't_f2',
                            'identifier' => 'f1',
                            'title' => 'F1',
                        ],
                        [
                            'type' => 't_f2',
                            'identifier' => 'f2',
                            'title' => 'F2',
                        ]
                    ]
                ]
            ],
        ]));
        $contentType = new ContentType();
        $contentType->addField($field);

        $data = [];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([], $data);

        $data = [
            'variants' => null,
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'variants' => null,
        ], $data);

        $data = [
            'variants' => [
                'type' => 'a',
                'a' => [],
            ],
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'variants' => [
                'type' => 'a',
                'a' => [
                    'f1' => 'FOO',
                ],
            ],
        ], $data);

        $data = [
            'variants' => [
                'type' => 'b',
                'b' => [],
            ],
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'variants' => [
                'type' => 'b',
                'b' => [],
            ],
        ], $data);

        $data = [
            'variants' => [
                'type' => 'a',
                'a' => [
                    'f1' => 'BAA',
                    'f2' => 'BAA',
                ],
            ],
        ];
        $manager->alterFieldData($field, $data, new Content(), $data);
        $this->assertEquals([
            'variants' => [
                'type' => 'a',
                'a' => [
                    'f1' => 'FOO',
                    'f2' => 'BAA',
                ],
            ],
        ], $data);
    }
}
