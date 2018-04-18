<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;

use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableField;

class ContentTypeEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $content_type = new ContentType();

        // test if parent returns null
        $this->assertEquals(null, $content_type->getParentEntity());
    }

    public function testSetViewsFromEntity()
    {
        $content_type = new ContentType();
        $content_type->setWeight(1);

        $content_type2 = new ContentType();
        $content_type2->setWeight(2);

        $view = new View();
        $view->setId(1)
            ->setTitle('Title')
            ->setType('bla')
            ->setIdentifier(1);

        $view2 = new View();
        $view2->setId(2)
            ->setTitle('Title2')
            ->setType('bla')
            ->setIdentifier(2);

        $view3 = new View();
        $view3->setId(3)
            ->setTitle('Title3')
            ->setType('bla')
            ->setIdentifier(3);

        $content_type->setViews(
            [
                $view,
            ]
        );

        $content_type2->setViews(
            [
                $view2,
                $view3,
            ]
        );

        $content_type->setFromEntity($content_type2);

        // should be 2 views, one deleted, 2 added with all
        $this->assertCount(3, $content_type->getViews());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFieldException()
    {
        $content_type = new ContentType();
        $test_field = $this->createMock(FieldableField::class);
        $content_type->addField($test_field);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetViewException()
    {
        $content_type = new ContentType();
        $content_type->getView(777);
    }
}