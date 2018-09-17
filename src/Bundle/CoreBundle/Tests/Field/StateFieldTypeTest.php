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
                'category' => 'notice'
            ],
            'review'=> [
                'category' => 'primary'
            ],
            'review2'  => [],
            'published'  => []
        ],
        'transitions' => [
            'draft'=> [
                'label' => 'Put into review mode',
                'from' => 'draft',
                'to' => 'review',
            ],
            'review'=> [
                'label' => 'Put into review mode',
                'from' => ['review2','review'],
                'to' => 'published',
            ],
            'review2' => [
                'label' => 'Put into review mode',
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
            'initial_place' => 'draft',
            'places' => [
                'draft' => [
                    'category' => 'red'
                ],
                'review'=> [],
                'review2' => [],
                '2published' => []
            ],
            'transitions' => [
                'draft'=> [
                    'label' => 'Put into review mode',
                    'from' => 'draft1',
                    'to' => 'review234',
                ],
                'review'=> [
                    'from' => ['review22','published'],
                    'to' => 'Publish Content',
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);

        # test invalid places category
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('invalid_place', $errors->get(0)->getCause());

        # test transition from in place
        $settings['places']['draft'] = [];
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('invalid_transition', $errors->get(0)->getCause());

        # test transition to in place
        $settings['transitions']['draft']['from'] = "draft";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('invalid_transition', $errors->get(0)->getCause());

        # test mission transition settings
        $settings['transitions']['draft']['to'] = "draft";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('invalid_transition', $errors->get(0)->getCause());

        # test initial place
        unset($settings['transitions']['review']);
        $settings['initial_place'] = "xxxyyy";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertEquals('invalid_place', $errors->get(0)->getCause());
    }

    public function testStateFieldTypeWithValidSettings()
    {

        $ctField = $this->createContentTypeField('state');
        $ctField->setSettings(new FieldableFieldSettings($this->settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

    }

    public function testStateFieldTypeWorkflowChange()
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