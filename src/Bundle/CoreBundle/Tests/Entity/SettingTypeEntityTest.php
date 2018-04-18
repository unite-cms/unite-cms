<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Tests\FakeField;

class SettingTypeEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $setting_type = new SettingType();
        $setting_type->setIdentifier('test123');

        $this->assertEquals($setting_type, $setting_type->getRootEntity());

        $this->assertEquals('test123', $setting_type->getIdentifierPath());

        $this->assertEquals(null, $setting_type->getParentEntity());

        $setting = new Setting();
        $setting->setLocale('de');

        $setting_type->setSettings(
            [
                $setting,
            ]
        );

        $this->assertCount(1, $setting_type->getSettings());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFieldException()
    {
        $setting_type = new SettingType();
        $setting_type->addField(new FakeField());
    }
}