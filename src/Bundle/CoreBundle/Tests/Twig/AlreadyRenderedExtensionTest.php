<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 16:46
 */

namespace UniteCMS\CoreBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Twig\AlreadyRenderedExtension;

class AlreadyRenderedExtensionTest extends TestCase
{

    public function testAlreadyRenderedTwigFunction() {
        $extension = new AlreadyRenderedExtension();
        $this->assertFalse($extension->alreadyRendered('key1'));
        $this->assertTrue($extension->alreadyRendered('key1'));
        $this->assertFalse($extension->alreadyRendered('key2'));
        $this->assertTrue($extension->alreadyRendered('key2'));
    }

}