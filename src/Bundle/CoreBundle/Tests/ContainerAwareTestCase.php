<?php

namespace UniteCMS\CoreBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ContainerAwareTestCase extends KernelTestCase
{
    protected function generateRandomMachineName($count = 0)
    {
        $return = '';
        $allowed = str_split('abcdefghijklmnopqrstuvwxyz0123456789_');

        for ($i = 0; $i < $count; $i++) {
            $return .= $allowed[random_int(0, count($allowed) - 1)];
        }

        // should start and end with an letter.
        if(substr($return, 0, 1) === '_') {
            $return = 'a'.substr($return, 1);
        }

        if(substr($return, -1, 1) === '_') {
            $return = substr($return, 0, -1).'a';
        }

        return $return;
    }

    protected function generateRandomUTF8String($count = 0)
    {
        return substr(base64_encode(random_bytes($count)), 0, $count);
    }

    public function setUp()
    {
        static::bootKernel(['debug' => false]);
    }
}
