<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;

class DomainEntityTest extends TestCase
{
    public function testBasicOperations()
    {
        $domain = new Domain();

        // test if domain has no content or settings types
        $this->assertEquals(false, $domain->hasContentOrSettingTypes());
    }

    public function testContentAndSettingsTypeDiff()
    {
        $domain = new Domain();
        $domain2 = new Domain();

        $content_type = new ContentType();
        $content_type->setId(1);
        $content_type->setIdentifier(1);

        $content_type2 = new ContentType();
        $content_type2->setId(2);
        $content_type2->setIdentifier(2);

        $settings_type = new SettingType();
        $settings_type->setId(1);
        $settings_type->setIdentifier(1);

        $settings_type2 = new SettingType();
        $settings_type2->setId(2);
        $settings_type2->setIdentifier(2);

        $domain->setContentTypes([$content_type]);
        $domain->setSettingTypes([$settings_type]);

        $domain2->setContentTypes([$content_type2]);
        $domain2->setSettingTypes([$settings_type2]);

        $this->assertCount(1, $domain->getContentTypesDiff($domain2, true));

        $this->assertCount(1, $domain->getSettingTypesDiff($domain2, true));
    }
}