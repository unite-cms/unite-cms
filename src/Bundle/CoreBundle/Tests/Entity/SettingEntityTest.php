<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Setting;

class SettingEntityTest extends TestCase
{

    public function testBasicOperations()
    {
        $setting = new Setting();

        $this->assertTrue($setting->isNew());

        $rct1_id = new \ReflectionProperty($setting, 'id');
        $rct1_id->setAccessible(true);
        $rct1_id->setValue($setting, 1);

        $this->assertEquals(1, $setting->getId());
        $this->assertFalse($setting->isNew());
    }

}