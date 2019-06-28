<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 16:38
 */

namespace UniteCMS\CoreBundle\Twig;

use ReflectionClass;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class InstanceOfExtension extends AbstractExtension
{
    public function getTests()
    {
        return [
            new TwigTest('instanceof', [$this, 'isInstanceOf']),
        ];
    }

    /**
     * Returns true, if $object is an instance of $class.
     *
     * @param object $object
     * @param string $class
     * @return bool
     *
     * @throws \ReflectionException
     */
    public function isInstanceOf($object, string $class) : bool {
        $reflectionClass = new ReflectionClass($class);
        return $reflectionClass->isInstance($object);
    }

}