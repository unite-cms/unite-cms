<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 16:46
 */

namespace UniteCMS\CoreBundle\Tests\Twig;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use UniteCMS\CoreBundle\Twig\InstanceOfExtension;

class InstanceOfExtensionTest extends TestCase
{

    public function testInstanceOfTwigFunction() {
        $extension = new InstanceOfExtension();
        $object = new ObjectType(['name' => 'foo' ]);

        $this->assertTrue($extension->isInstanceOf($object, Type::class));
        $this->assertTrue($extension->isInstanceOf($object, ObjectType::class));
        $this->assertFalse($extension->isInstanceOf($object, AbstractController::class));
        $this->assertFalse($extension->isInstanceOf(new \stdClass(), Type::class));
    }

    /**
     * @expectedException \ReflectionException
     */
    public function testReflectionExceptionWithString() {
        $extension = new InstanceOfExtension();
        $this->assertFalse($extension->isInstanceOf('foo', 'baa'));
    }

    /**
     * @expectedException \ReflectionException
     */
    public function testReflectionExceptionWithObject() {
        $extension = new InstanceOfExtension();
        $object = new ObjectType(['name' => 'foo' ]);
        $this->assertFalse($extension->isInstanceOf($object, 'baa'));
    }

}