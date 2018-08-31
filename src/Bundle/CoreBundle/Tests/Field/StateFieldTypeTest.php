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
        $this->assertCount(3, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testStateFieldTypeWithInvalidSettings()
    {

        $ctField = $this->createContentTypeField('state');

        $settings = [
            'initial_place' => 'draft1',
            'places' => [
                'draft' => [],
                'review2',
                'review2',
                '2published'
            ],
            'transitions' => [
                'draft'=> [
                    'label' => 'Put into review mode',
                    'from' => 'draft',
                    'to' => 'review234',
                ],
                'review'=> [
                    'from' => ['review22','published'],
                    'to' => 'Publish Content',
                ],
                'review566' => [
                    'from' => 'review2'
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);

        # test invalid initial place
        $this->assertEquals('invalid_initial_place', $errors->get(0)->getMessageTemplate());

        # test places values all strings
        $this->assertEquals('invalid_places', $errors->get(0)->getMessageTemplate());

        # test invalid from to transition, no place
        $this->assertEquals('invalid_transitions', $errors->get(0)->getMessageTemplate());

        # test all transition params set
        $this->assertEquals('invalid_transitions', $errors->get(0)->getMessageTemplate());


    }

    public function testStateFieldTypeWithValidSettings()
    {

        $ctField = $this->createContentTypeField('state');

        $settings = [
            'initial_place' => 'draft',
            'places' => [
                'draft',
                'review',
                'review2',
                'published'
            ],
            'transitions' => [
                'draft'=> [
                    'label' => 'Put into review mode',
                    'from' => 'draft',
                    'to' => 'review',
                ],
                'review'=> [
                    'label' => 'Put into review mode',
                    'from' => ['review2','published'],
                    'to' => 'Publish Content',
                ],
                'review2' => [
                    'from' => 'review2',
                    'to' => 'Publish Content'
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}