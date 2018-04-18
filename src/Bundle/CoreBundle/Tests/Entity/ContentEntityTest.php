<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Content;

class ContentEntityTest extends TestCase
{
    public function testExistingTranslation()
    {
        $content = new Content();

        $content_tr1 = new Content();

        $rct1_id = new \ReflectionProperty($content_tr1, 'id');
        $rct1_id->setAccessible(true);
        $rct1_id->setValue($content_tr1, 1);

        $content_tr2 = new Content();

        $rct2_id = new \ReflectionProperty($content_tr2, 'id');
        $rct2_id->setAccessible(true);
        $rct2_id->setValue($content_tr2, 2);

        $content_tr1->setTranslationOf($content_tr2);

        $content->setTranslationOf($content_tr1);

        $this->assertEquals(2, $content->getTranslationOf()->getId());
    }
}