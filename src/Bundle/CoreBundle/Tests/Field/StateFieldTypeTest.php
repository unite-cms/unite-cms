<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 31.08.18
 * Time: 14:33
 */

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class StateFieldTypeTest extends FieldTypeTestCase
{

    public function testStateFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('state');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testValidateStates()
    {



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
        $this->assertContains('not a valid category', $errors->get(0)->getMessageTemplate());

        # test transition from in place
        $settings['places']['draft'] = [];
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertContains('referenced in from transition', $errors->get(0)->getMessageTemplate());

        # test transition to in place
        $settings['transitions']['draft']['from'] = "draft";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertContains('referenced in to transition', $errors->get(0)->getMessageTemplate());

        # test mission transition settings
        $settings['transitions']['draft']['to'] = "draft";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertContains('Missing Transition Settings', $errors->get(0)->getMessageTemplate());

        # test initial place
        unset($settings['transitions']['review']);
        $settings['initial_place'] = "xxxyyy";
        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertContains('initial place as it does not exist', $errors->get(0)->getMessageTemplate());
    }

    public function testStateFieldTypeWithValidSettings()
    {

        $ctField = $this->createContentTypeField('state');

        $settings = [
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

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

    }
}