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

        $this->contentType
            ->addField($ctField1)
            ->addField($ctField2);

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

        // Create and persist two content objects
        $content1 = new Content();
        $content1->setData(['f1' => 'Foo', 'f2' => 'Foo_f2'])->setContentType($this->contentType);

        $content2 = new Content();
        $content2->setData([])->setContentType($this->contentType);

        $content21 = new Content();
        $content21->setData(['f1' => 'Baa'])->setContentType($this->contentType2);

        $this->em->persist($content1);
        $this->em->persist($content2);
        $this->em->persist($content21);
        $this->em->flush();

        $this->assertEquals([
            'f1' => 'Foo',
            'f2' => 'Foo_f2'
        ], $content1->getData());

        $this->assertEquals([], $content2->getData());

        // Now check if the same value would be unique.
        $this->assertFalse($this->expressionChecker->evaluateToBool('content_unique("Foo", "f1")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique("foo", "f1")'));

        // Baa value in f1 is only in content from other content type.
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique("Baa", "f1")'));

        // Use content object value as input.
        $newContent = new Content();
        $newContent->setData(['f1' => 'Foo']);
        $this->expressionChecker->registerFieldableContent($newContent);
        $this->assertFalse($this->expressionChecker->evaluateToBool('content_unique(content.data.f1, "f1")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique(content.data.f1, "f2")'));
        $this->assertTrue($this->expressionChecker->evaluateToBool('content_unique(slug(content.data.f1), "f2")'));
    }

    public function testUniqueValidation() {

        $this->contentType->addValidation(new FieldableValidation('content_unique(content.data.f1, "f1")', 'invalid_f1'));
        $this->contentType->addValidation(new FieldableValidation('content_unique(slug(content.data.f2), "f2")', 'invalid_f2'));

        // Create and persist a content object
        $content1 = new Content();
        $content1->setData(['f1' => 'Foo', 'f2' => 'foo-f2'])->setContentType($this->contentType);
        $this->em->persist($content1);
        $this->em->flush();

        // Now try to validate another content with the same field values.
        $content2 = new Content();
        $content2->setData(['f1' => 'Foo', 'f2' => 'Foo_f2'])->setContentType($this->contentType);

        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(2, $errors);
        $this->assertEquals('invalid_f1', $errors->get(0)->getMessage());
        $this->assertEquals('invalid_f2', $errors->get(1)->getMessage());

        $content2->setData(['f1' => 'Baa', 'f2' => 'foo-f2']);
        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_f2', $errors->get(0)->getMessage());

        $content2->setData(['f1' => 'Baa', 'f2' => 'Foo_f2_foo']);
        $errors = static::$container->get('validator')->validate($content2);
        $this->assertCount(0, $errors);
    }
}
