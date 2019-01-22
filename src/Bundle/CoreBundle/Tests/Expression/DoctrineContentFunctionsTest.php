<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-07
 * Time: 14:26
 */

namespace UniteCMS\CoreBundle\Tests\Expression;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DoctrineContentFunctionsTest extends DatabaseAwareTestCase
{
    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var ContentType $contentType
     */
    private $contentType;

    /**
     * @var ContentType $contentType2
     */
    private $contentType2;

    /**
     * @var UniteExpressionChecker $expressionChecker
     */
    private $expressionChecker;

    public function setUp() {

        parent::setUp();

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('org');

        $this->domain = new Domain();
        $this->domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);

        $this->contentType = new ContentType();
        $this->contentType->setIdentifier('ct')->setTitle('CT')->setDomain($this->domain);

        $ctField1 = new ContentTypeField();
        $ctField1->setTitle('f1')->setIdentifier('f1')->setType('text');

        $ctField2 = new ContentTypeField();
        $ctField2->setTitle('f2')->setIdentifier('f2')->setType('text');

        $ctField3 = new ContentTypeField();
        $ctField3->setTitle('f3')->setIdentifier('f3')->setType('auto_text')
            ->setSettings(new FieldableFieldSettings([
                'expression' => 'content_uniquify(slug(content.data.f2), "f3.text")',
                'auto_update' => true,
            ]));

        $this->contentType
            ->addField($ctField1)
            ->addField($ctField2)
            ->addField($ctField3);

        $this->contentType2 = new ContentType();
        $this->contentType2->setIdentifier('ct2')->setTitle('CT2')->setDomain($this->domain);

        $ctField21 = new ContentTypeField();
        $ctField21->setTitle('f1')->setIdentifier('f1')->setType('text');
        $this->contentType2->addField($ctField21);

        $this->em->persist($org);
        $this->em->persist($this->domain);
        $this->em->persist($this->contentType);
        $this->em->persist($this->contentType2);
        $this->em->flush();

        $this->expressionChecker = new UniteExpressionChecker();
        $this->expressionChecker->registerDoctrineContentFunctionsProvider($this->em, $this->contentType);
    }

    public function testContentUniqueFunction() {

        // Test with invalid argument types.
        $this->assertEquals(false, $this->expressionChecker->evaluateToBool('content_unique(["foo"], "f1")'));
        $this->assertEquals(false, $this->expressionChecker->evaluateToBool('content_unique("foo", 23'));

        // Create and persist two content objects
        $content1 = new Content();
        $content1->setContentType($this->contentType);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content1, ['f1' => 'Foo', 'f2' => 'Foo_f2']);

        $content2 = new Content();
        $content2->setContentType($this->contentType);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content2, []);

        $content21 = new Content();
        $content21->setContentType($this->contentType2);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content21, ['f1' => 'Baa']);

        $this->em->persist($content1);
        $this->em->persist($content2);
        $this->em->persist($content21);
        $this->em->flush();

        $this->assertEquals([
            'f1' => 'Foo',
            'f2' => 'Foo_f2',
            'f3' => [
                'auto' => true,
                'text' => 'foo-f2',
            ],
        ], $content1->getData());

        $this->assertEquals([
            'f3' => [
                'auto' => true,
                'text' => '',
            ],
        ], $content2->getData());

        // Now check if the same value would be unique.
        $this->assertFalse($this->expressionChecker->evaluateToBool('content_unique("Foo", "f1")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique("foo", "f1")'));

        // Baa value in f1 is only in content from other content type.
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique("Baa", "f1")'));

        // Use content object value as input.
        $newContent = new Content();
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($newContent, ['f1' => 'Foo']);
        $this->expressionChecker->registerFieldableContent($newContent);
        $this->assertFalse($this->expressionChecker->evaluateToBool('content_unique(content.data.f1, "f1")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique(content.data.f1, "f2")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique(slug(content.data.f1), "f2")'));
    }

    public function testContentUniquifyFunction() {

        // Test with invalid argument types.
        $this->assertEquals(true, $this->expressionChecker->evaluateToString('content_uniquify(true, "f1")'));
        $this->assertEquals(false, $this->expressionChecker->evaluateToString('content_uniquify(false, "f1")'));
        $this->assertEquals("foo", $this->expressionChecker->evaluateToString('content_uniquify("foo", 23)'));
        $this->assertEquals('', $this->expressionChecker->evaluateToString('content_uniquify(["foo"], "f1")'));

        // Create a uniquify when no content was stored before
        $content1 = new Content();
        $content1->setContentType($this->contentType);
        $this->expressionChecker->registerFieldableContent($content1);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content1, [
            'f1' => $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title"), "f1")'),
            'f2' => 'Foo BAA',
        ]);
        $this->assertEquals(['f1' => 'this-is-my-title', 'f2' => 'Foo BAA', 'f3' => [
            'auto' => true,
            'text' => 'foo-baa',
        ]], $content1->getData());
        $this->em->persist($content1);
        $this->em->flush();

        // Now uniquify the same string, should be uniquified
        $content2 = new Content();
        $content2->setContentType($this->contentType);
        $this->expressionChecker->registerFieldableContent($content2);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content2, [
            'f1' => $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title       "), "f1")'),
            'f2' => 'Foo BAA',
        ]);
        $this->assertEquals([
            'f1' => 'this-is-my-title-1',
            'f3' => [
                'auto' => true,
                'text' => 'foo-baa-1'
            ],
            'f2' => 'Foo BAA',
        ], $content2->getData());
        $this->em->persist($content2);
        $this->em->flush();

        // Now create a content that would have the next unique suffix
        $content3 = new Content();
        $content3->setContentType($this->contentType);
        $this->expressionChecker->registerFieldableContent($content3);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content3, [
            'f1' => 'this-is-my-title-2',
            'f2' => 'Foo BAA 2',
        ]);
        $this->em->persist($content3);
        $this->em->flush();

        // Because this-is-my-title-2 is already taken, the next uniquify call should suffix 3
        $content4 = new Content();
        $content4->setContentType($this->contentType);
        $this->expressionChecker->registerFieldableContent($content4);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content4, [
            'f1' => $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title"), "f1")'),
            'f2' => 'Foo BAA',
        ]);
        $this->assertEquals([
            'f1' => 'this-is-my-title-3',
            'f3' => [
                'auto' => true,
                'text' => 'foo-baa-3'
            ],
            'f2' => 'Foo BAA',
        ], $content4->getData());

        // Save this content with suffix 4. uniquify will first try to add suffix based on the sql count of LIKE $value%.
        // This would produce 4. Because 4 is already taken, it should add an additional suffix.
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content4, ['f1' => 'this-is-my-title-4']);
        $this->em->persist($content4);
        $this->em->flush();

        // Saving a content with the same prefix bug not a numeric suffix should not change anything.
        $content5 = new Content();
        $content5->setContentType($this->contentType);
        $this->expressionChecker->registerFieldableContent($content4);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content5, [
            'f1' => 'this-is-my-title-a',
            'f2' => 'foo-baa-a'
        ]);
        $this->em->persist($content5);
        $this->em->flush();

        // Now test different uiquify calls based on the existing content
        $this->assertEquals('this-is-my-title-5', $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title"), "f1")'));
        $this->assertEquals('this-is-my-title', $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title"), "f2")'));
        $this->assertEquals('this-is-my-title-3', $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title 3"), "f1")'));
        $this->assertEquals('this-is-my-title-4-1', $this->expressionChecker->evaluateToString('content_uniquify(slug("This is my title 4"), "f1")'));
    }

    public function testUniqueValidation() {

        $this->contentType->addValidation(new FieldableValidation('content_unique(content.data.f1, "f1")', 'invalid_f1'));
        $this->contentType->addValidation(new FieldableValidation('content_unique(slug(content.data.f2), "f2")', 'invalid_f2'));

        // Create and persist a content object
        $content1 = new Content();
        $content1->setContentType($this->contentType);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content1, ['f1' => 'Foo', 'f2' => 'foo-f2']);
        $this->em->persist($content1);
        $this->em->flush();

        // Now try to validate another content with the same field values.
        $content2 = new Content();
        $content2->setContentType($this->contentType);
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content2, ['f1' => 'Foo', 'f2' => 'Foo_f2']);

        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(2, $errors);
        $this->assertEquals('invalid_f1', $errors->get(0)->getMessage());
        $this->assertEquals('invalid_f2', $errors->get(1)->getMessage());

        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content2, ['f1' => 'Baa', 'f2' => 'foo-f2']);
        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_f2', $errors->get(0)->getMessage());

        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content2, ['f1' => 'Baa', 'f2' => 'Foo_f2_foo']);
        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(0, $errors);
    }
}
