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
            'to_draft'=> [
                'label' => 'Back to draft',
                'from' => [ 'published', 'review' ],
                'to' => 'draft',
            ],
            'to_review'=> [
                'label' => 'Put into review mode',
                'from' => [ 'draft' ],
                'to' => 'review',
            ],
            'to_review2'=> [
                'label' => 'Put into review 2 mode',
                'from' => [ 'review' ],
                'to' => 'review2',
            ],
            'to_published' => [
                'label' => 'Publish Content',
                'from' => [ 'review2' ],
                'to' => 'published'
            ]
        ]
    ];

    public function testStateFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('state');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('required', $errors->get(2)->getMessageTemplate());
    }

    public function testStateFieldTypeWithInvalidSettings()
    {

        $ctField = $this->createContentTypeField('state');

        // check for completely crap
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

        // check for invalid places
        $settings = [
            'initial_place' => 'draft123123',
            'places' => [
                'review234234'=> true,
                'draft23' => [
                    'category' => ['red'],
                    'label' => true,
                    'fofofof' => ''
                ],
                'draft' => [
                    'label' => 'Draft',
                    'fofofof' => '',
                    'category' => true
                ],
                'dra&%%ft2' => [
                    'label' => $this->generateRandomMachineName(500),
                ],
                'review'=> [
                    'label' =>  ['red']
                ],
            ],
            'transitions' => [
                'to_review'=> [
                    'label' => 'Put into review mode',
                    'from' => ['draft'],
                    'to' => 'review',
                ],
                'to_review2'=> [
                    'label' => $this->generateRandomMachineName(500),
                    'from' => ['draft'],
                    'to' => 'review',
                ],
                'to_r&%%eview3' => [
                    'label' => 'Put into review mode',
                    'from' => ['draft'],
                    'to' => 'review',
                ],
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(12, $errors);

        $this->assertEquals('workflow_invalid_places', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_initial_place', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_category', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_category', $errors->get(6)->getMessageTemplate());
        $this->assertEquals('invalid_characters', $errors->get(7)->getMessageTemplate());
        $this->assertEquals('too_long', $errors->get(8)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_place', $errors->get(9)->getMessageTemplate());
        $this->assertEquals('too_long', $errors->get(10)->getMessageTemplate());
        $this->assertEquals('invalid_characters', $errors->get(11)->getMessageTemplate());

         // check for invalid transitions
        $settings = [
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
                    'from' => [ 'draft1' ],
                    'to' => 'review234',
                    'fofofof' => ''
                ],
                'to_review2'=> [
                    'label' => 'Put into review mode',
                    'from' => [
                         'draft1' => ['test']
                    ],
                    'to' => 'review234',
                ],
                'tp_published'=> [
                    'label' => 'Put into review mode',
                    'from' => ['review22','published34'],
                    'to' => 'Publish Content',
                ]
            ]
        ];

        $ctField->setSettings(new FieldableFieldSettings($settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(8, $errors);

        $this->assertEquals('workflow_invalid_transition', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_from', $errors->get(6)->getMessageTemplate());
        $this->assertEquals('workflow_invalid_transition_to', $errors->get(7)->getMessageTemplate());

    }

    public function testStateFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('state');
        $ctField->setSettings(new FieldableFieldSettings($this->settings));
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testStateFieldTypeTestFormSubmit()
    {
        $ctField = $this->createContentTypeField('state');
        $ctField->setSettings(new FieldableFieldSettings($this->settings));

        $content = new Content();
        $content->setContentType($ctField->getContentType());

        // test a transition choice
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
                'csrf_protection' => false,
            ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                    'transition' => 'tox_published'
                ],
            ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $error_check = [];
        foreach ($form->getErrors(true, true) as $error) {
            $error_check[] = $error->getMessageTemplate();
        }

        $this->assertCount(1, $error_check);
        $this->assertEquals('This value is not valid.', $error_check[0]);

        // test wrong formats
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
                'csrf_protection' => false,
            ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                    'state' => [],
                    'transition' => null
                ],
            ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $error_check = [];
        foreach ($form->getErrors(true, true) as $error) {
            $error_check[] = $error->getMessageTemplate();
        }
        $this->assertCount(1, $error_check);
        $this->assertEquals('workflow_invalid_place', $error_check[0]);

        // test a invalid transition
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
                'csrf_protection' => false,
            ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                       'state' => 'review',
                       'transition' => 'to_published'
                ],
            ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $error_check = [];
        foreach ($form->getErrors(true, true) as $error) {
            $error_check[] = $error->getMessageTemplate();
        }

        $this->assertCount(1, $error_check);
        $this->assertEquals('workflow_transition_not_allowed', $error_check[0]);

        // test a second invalid transition
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                    'state' => 'review2',
                    'transition' => 'to_draft'
                ],
            ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $error_check = [];
        foreach ($form->getErrors(true, true) as $error) {
            $error_check[] = $error->getMessageTemplate();
        }

        $this->assertCount(1, $error_check);
        $this->assertEquals('workflow_transition_not_allowed', $error_check[0]);

        // test a initial valid transition
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]
        );

        $form->submit(
        [
            $ctField->getIdentifier() => [
                'transition' => 'to_review'
            ],
        ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertEquals('review', $form->get($ctField->getIdentifier())->getData());

        // test a valid transition
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
                'csrf_protection' => false,
            ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                    'state' => 'draft',
                    'transition' => 'to_review'
                ],
            ]
        );

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertEquals('review', $form->get($ctField->getIdentifier())->getData());

        $content->setData(
            []
        );

        // test empty values
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
                'csrf_protection' => false,
            ]
        );

        $form->submit(
            [
                $ctField->getIdentifier() => [
                    'state' => '',
                    'transition' => ''
                ]
            ]
        );

        $this->assertEquals('draft', $form->get($ctField->getIdentifier())->getData());

    }

}