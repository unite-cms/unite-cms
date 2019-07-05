<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 15:41
 */

namespace UniteCMS\VariantsFieldBundle\Tests\Field;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class FieldEventHooksTest extends DatabaseAwareTestCase
{
    private $domainConfig = '
    {
        "title": "Domain",
        "identifier": "d",
        "content_types": [
            {
                "title": "CT 1",
                "identifier": "ct1",
                "fields": [
                    {
                        "title": "Nested Level 1",
                        "identifier": "n1",
                        "type": "variants",
                        "settings": {
                            "variants": [
                                {
                                    "title": "V1",
                                    "identifier": "v1",
                                    "fields": [
                                        {
                                            "title": "Nested Level V1 2",
                                            "identifier": "n2",
                                            "type": "testeventhook"
                                        }
                                    ] 
                                },
                                {
                                    "title": "V2",
                                    "identifier": "v2",
                                    "fields": [
                                        {
                                            "title": "Nested Level V2 - 2",
                                            "identifier": "n2",
                                            "type": "testeventhook"
                                        }
                                    ] 
                                }
                            ]
                        }
                    }
                ]
            }
        ],
        "setting_types": [
            {
                "title": "ST 1",
                "identifier": "st1",
                "fields": [
                    {
                        "title": "Nested Level 1",
                        "identifier": "n1",
                        "type": "variants",
                        "settings": {
                            "variants": [
                                {
                                    "title": "V1",
                                    "identifier": "v1",
                                    "fields": [
                                        {
                                            "title": "Nested Level V1 2",
                                            "identifier": "n2",
                                            "type": "testeventhook"
                                        }
                                    ] 
                                },
                                {
                                    "title": "V2",
                                    "identifier": "v2",
                                    "fields": [
                                        {
                                            "title": "Nested Level V2 - 2",
                                            "identifier": "n2",
                                            "type": "testeventhook"
                                        }
                                    ] 
                                }
                            ]
                        }
                    }
                ]
            }
        ]
    }';

    /**
     * @var Domain $domain
     */
    private $domain;

    public function setUp()
    {
        parent::setUp();

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');
        $this->em->persist($org);
        $this->em->flush($org);

        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfig);
        $this->domain->setOrganization($org);
        $this->em->persist($this->domain);
        $this->em->flush($this->domain);
    }

    protected function createFieldTypeMock() {
        $mock = new class extends FieldType
        {
            public $events = [];
            public function createCompareAbleString(
                FieldableField $field,
                FieldableContent $content,
                EntityRepository $repository,
                $data = '',
                $data2 = ''
            ) {
                return
                    $field->getJsonExtractIdentifier().
                    ($content->getEntity() ? $content->getEntity()->getIdentifier() : '').
                    $repository->getClassName().
                    $data.
                    $data2;
            }

            const TYPE = 'testeventhook';

            public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data)
            {
                $this->events[] = $this->createCompareAbleString($field, $content, $repository, 'create', $data[$field->getIdentifier()]);
                $data[$field->getIdentifier()] .= '.onCreate';

            }

            public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
                $this->events[] = $this->createCompareAbleString($field, $content, $repository, 'update', $data[$field->getIdentifier()]);
                $data[$field->getIdentifier()] .= '.onUpdate';
            }

            public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data)
            {
                $this->events[] = $this->createCompareAbleString($field, $content, $repository, 'soft_delete');
            }

            public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data)
            {
                $this->events[] = $this->createCompareAbleString($field, $content, $repository, 'hard_delete');
            }
        };
        static::$container->get('unite.cms.field_type_manager')->registerFieldType($mock);
        return $mock;
    }

    public function testNestedEventsForContent()
    {
        $mock = $this->createFieldTypeMock();

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first());

        // Create variant 1
        $mock->events = [];
        $content->setData(
            [
                'n1' => [
                    'type' => 'v1',
                    'v1' => [
                        'n2' => 'foo'
                    ],
                ],
            ]
        );

        $this->em->persist($content);
        $this->em->flush($content);
        $this->em->refresh($content);

        // Make sure, that nested create event was fired on fields for variant 1 but not variant 2.
        $this->assertEquals([
            '$.n1.v1.n2ct1'.Content::class.'createfoo',
        ], $mock->events);
        $this->assertEquals('foo.onCreate', $content->getData()['n1']['v1']['n2']);

        // Update fields but do not change variant.
        $mock->events = [];
        $content->setData(
            [
                'n1' => [
                    'type' => 'v1',
                    'v1' => [
                        'n2' => 'laa'
                    ],
                ],
            ]
        );
        $this->em->flush($content);

        // Make sure, that nested update event was fired on fields for variant 1.
        $this->assertEquals([
            '$.n1.v1.n2ct1'.Content::class.'updatelaa',
        ], $mock->events);
        $this->assertEquals('laa.onUpdate', $content->getData()['n1']['v1']['n2']);

        // Change type.
        $mock->events = [];
        $content->setData(
            [
                'n1' => [
                    'type' => 'v2',
                    'v2' => [
                        'n2' => 'foo'
                    ],
                ],
            ]
        );
        $this->em->flush($content);

        // Make sure, that delete was fired for variant 1 fields and create for variant 2 fields.
        $this->assertEquals([
            '$.n1.v1.n2ct1'.Content::class.'soft_delete',
            '$.n1.v2.n2ct1'.Content::class.'createfoo',
        ], $mock->events);
        $this->assertEquals('foo.onCreate', $content->getData()['n1']['v2']['n2']);

        // Remove data completely
        $mock->events = [];
        $content->setData(
            [
                'n1' => [],
            ]
        );
        $this->em->flush($content);

        // Make sure, that delete was fired for variant 2 and nothing was fired for variant 1.
        $this->assertEquals([
            '$.n1.v2.n2ct1'.Content::class.'soft_delete',
        ], $mock->events);
        $this->assertEquals([], $content->getData()['n1']);


        // Reset content.
        $mock->events = [];
        $content->setData(
            [
                'n1' => [
                    'type' => 'v2',
                    'v2' => [
                        'n2' => 'foo'
                    ],
                ],
            ]
        );
        $this->em->flush($content);

        // Soft delete content
        $mock->events = [];
        $this->em->remove($content);
        $this->em->flush();

        // softDelete should be invoked for all nested fields for the current variant.
        $this->assertEquals([
            '$.n1.v2.n2ct1'.Content::class.'soft_delete',
        ], $mock->events);

        // Remove it for real.
        $this->em->getFilters()->disable('gedmo_softdeleteable');

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
            [
                'contentType' => $this->domain->getContentTypes()->first(),
            ]
        );

        $mock->events = [];
        $this->em->remove($content);
        $this->em->flush();
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // hardDelete should be invoked for all nested fields for the current variant.
        $this->assertEquals([
            '$.n1.v2.n2ct1'.Content::class.'hard_delete',
        ], $mock->events);
    }

    public function testNestedEventsForSetting()
    {
        $mock = $this->createFieldTypeMock();

        $setting = new Setting();
        $setting->setSettingType($this->domain->getSettingTypes()->first());

        // Create variant 1
        $mock->events = [];
        $setting->setData(
            [
                'n1' => [
                    'type' => 'v1',
                    'v1' => [
                        'n2' => 'foo'
                    ],
                ],
            ]
        );

        $this->em->persist($setting);
        $this->em->flush($setting);
        $this->em->refresh($setting);

        // Make sure, that nested create event was fired on fields for variant 1 but not variant 2.
        $this->assertEquals([
            '$.n1.v1.n2st1'.Setting::class.'createfoo',
        ], $mock->events);
        $this->assertEquals('foo.onCreate', $setting->getData()['n1']['v1']['n2']);

        // Update fields but do not change variant.
        $mock->events = [];
        $setting->setData(
            [
                'n1' => [
                    'type' => 'v1',
                    'v1' => [
                        'n2' => 'laa'
                    ],
                ],
            ]
        );
        $this->em->flush($setting);

        // Make sure, that nested update event was fired on fields for variant 1.
        $this->assertEquals([
            '$.n1.v1.n2st1'.Setting::class.'updatelaa',
        ], $mock->events);
        $this->assertEquals('laa.onUpdate', $setting->getData()['n1']['v1']['n2']);

        // Change type.
        $mock->events = [];
        $setting->setData(
            [
                'n1' => [
                    'type' => 'v2',
                    'v2' => [
                        'n2' => 'foo'
                    ],
                ],
            ]
        );
        $this->em->flush($setting);

        // Make sure, that delete was fired for variant 1 fields and create for variant 2 fields.
        $this->assertEquals([
            '$.n1.v1.n2st1'.Setting::class.'soft_delete',
            '$.n1.v2.n2st1'.Setting::class.'createfoo',
        ], $mock->events);
        $this->assertEquals('foo.onCreate', $setting->getData()['n1']['v2']['n2']);

        // Remove data completely
        $mock->events = [];
        $setting->setData(
            [
                'n1' => [],
            ]
        );
        $this->em->flush($setting);

        // Make sure, that delete was fired for variant 2 and nothing was fired for variant 1.
        $this->assertEquals([
            '$.n1.v2.n2st1'.Setting::class.'soft_delete',
        ], $mock->events);
        $this->assertEquals([], $setting->getData()['n1']);
    }
}
