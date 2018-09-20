<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 31.08.18
 * Time: 14:33
 */

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

class StateFieldTypeTest extends FieldTypeTestCase
{
    private $settings = [
        'initial_place' => 'draft',
        'places' => [
            'draft' => [
                'label' => 'Draft',
                'category' => 'notice'
            ],
            'review'=> [
                'label' => 'Review',
                'category' => 'primary'
            ],
            'review2'=> [
                'label' => 'Review2',
                'category' => 'primary'
            ],
            'published' => [
                'label' => 'Published',
                'category' => 'primary'
            ],
        ],
        'transitions' => [
            'to_review'=> [
                'label' => 'Put into review mode',
                'from' => 'draft',
                'to' => 'review',
            ],
            'to_review2'=> [
                'label' => 'Put into review 2 mode',
                'from' => ['review','draft'],
                'to' => 'review',
            ],
            'to_published' => [
                'label' => 'Publish Content',
                'from' => 'review2',
                'to' => 'published'
            ]
        ]
    ];

    public function testStateFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('state');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testStateFieldTypeWithInvalidSettings()
    {

        $ctField = $this->createContentTypeField('state');

        $settings = [
            'initial_place' => [],
            'places' => "",
            'transitions' => true
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);

        $this->assertEquals('workflow_invalid_initial_place', $errors->get(0)->getMessageTemplate());
        
        $settings['initial_place'] = "draft";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('workflow_invalid_places', $errors->get(0)->getMessageTemplate());
        
        $settings['places'] = [];
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('workflow_invalid_transitions', $errors->get(0)->getMessageTemplate());

        $settings = [
            'initial_place' => 'draft123123',
            'places' => [
                'draft' => "",
                'review'=> true
            ],
            'transitions' => [
                'to_review' => "tst",
                'tp_published' => [
                    'label' => [],
                    'from' => ['review22','published'],
                    'to' =>  ['Publish Content']
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(13, $errors);
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_initial_place', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(6)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition', $errors->get(7)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(8)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(9)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition', $errors->get(10)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition', $errors->get(11)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(12)->getMessageTemplate());

        $settings = [
            'initial_place' => 'draft123123',
            'places' => [
                'draft' => [
                    'category' => ['red'],
                    'label' => true,
                    'fofofof' => ''
                ],
                'review' => [
                    'category' => 'red',
                    'label' =>['red']
                ],
                'review2' => [],
                '2published' => []
            ],
            'transitions' => [
                'to_review'=> [
                    'label' => 'Put into review mode',
                    'from' => 'draft1',
                    'to' => 'review234',
                    'fofofof' => ''
                ],
                'tp_published'=> [
                    'from' => ['review22','published'],
                    'to' => 'Publish Content',
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(14, $errors);

        $this->assertEquals('workflow_invalid_transition_from', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_initial_place', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(6)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_category', $errors->get(7)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_category', $errors->get(8)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(9)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_category', $errors->get(10)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(11)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(12)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition', $errors->get(13)->getMessageTemplate());
    }

    public function testStateFieldTypeWithValidSettings()
    {

        $ctField = $this->createContentTypeField('state');
        $ctField->setSettings(new FieldableFieldSettings($this->settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testStateFieldTypeWorkflows()
    {

        $ctField = $this->createContentTypeField('state');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');

        $ctField->setSettings(new FieldableFieldSettings($this->settings));

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier("domain");
        $domain->setOrganization($ctField->getContentType()->getDomain()->getOrganization());

        $contentType = new ContentType();
        $contentType->setIdentifier('baa')->setTitle('Baaa')->setDescription('TEST')->setDomain($domain);

        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($contentType);

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->persist($domain);
        $this->em->persist($contentType);
        $this->em->flush();

        $content = new Content();

        $content->setData([
            $ctField->getIdentifier() => 'draft',
        ]);

        $content->setContentType($contentType);

        $this->em->persist($content);
        $this->em->flush();

        exit;

    }

}