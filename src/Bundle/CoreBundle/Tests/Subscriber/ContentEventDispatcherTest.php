<?php

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ContentEventDispatcherTest extends DatabaseAwareTestCase
{
    private $mockedFieldType;
    private $contentType;
    private $settingType;

    public function setUp()
    {
        parent::setUp();

        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain');
        $org->addDomain($domain);
        $this->contentType = new ContentType();
        $this->settingType = new SettingType();
        $this->contentType->setTitle('Ct1')->setIdentifier('ct1');
        $this->settingType->setTitle('St1')->setIdentifier('st1');

        $f1 = new ContentTypeField();
        $f1->setTitle('f1')->setIdentifier('f1')->setType('mockedtype');
        $this->contentType->addField($f1);

        $f2 = new SettingTypeField();
        $f2->setTitle('f2')->setIdentifier('f2')->setType('mockedtype');
        $this->settingType->addField($f2);

        $domain->addContentType($this->contentType)->addSettingType($this->settingType);
        $this->em->persist($org);
        $this->em->persist($domain);

        $this->mockedFieldType = new class extends FieldType {
            public $events = [];
            static function getType(): string { return 'mockedtype'; }
            public function a($event, $args) {
                $this->events[] = [
                    'event' => $event,
                    'field' => $args[0],
                    'fieldablecontent' => $args[1],
                    'repository' => $args[2],
                    'data' => $args[3],
                    'new_data' => $args[4]?? null,
                ];
            }
            public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) { $this->a('onCreate', func_get_args()); }
            public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) { $this->a('onUpdate', func_get_args()); }
            public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) { $this->a('onSoftDelete', func_get_args()); }
            public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) { $this->a('onHardDelete', func_get_args()); }
        };
        static::$container->get('unite.cms.field_type_manager')->registerFieldType($this->mockedFieldType);
    }

    public function testCRUDShouldTriggerEvents()
    {
        // Create content
        $this->mockedFieldType->events = [];
        $content = new Content();
        $content->setContentType($this->contentType)->setData(['f1' => 'foo']);
        $this->em->persist($content);
        $this->em->flush($content);

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onCreate', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($content, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals($content->getData(), $this->mockedFieldType->events[0]['data']);

        // Update content
        $this->mockedFieldType->events = [];
        $content->setData(['f1' => 'baa']);
        $this->em->flush($content);

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onUpdate', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($content, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals(['f1' => 'foo'], $this->mockedFieldType->events[0]['data']);
        $this->assertEquals($content->getData(), $this->mockedFieldType->events[0]['new_data']);

        // Soft delete content
        $this->mockedFieldType->events = [];
        $this->em->remove($content);
        $this->em->flush($content);

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onSoftDelete', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($content, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals($content->getData(), $this->mockedFieldType->events[0]['data']);

        // Delete content
        $this->mockedFieldType->events = [];
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(['contentType' => $this->contentType]);
        $this->em->remove($content);
        $this->em->flush();
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onHardDelete', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($content, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals($content->getData(), $this->mockedFieldType->events[0]['data']);

        // Create Setting
        $this->mockedFieldType->events = [];
        $setting = new Setting();
        $setting->setSettingType($this->settingType)->setData(['f2' => 'foo']);
        $this->em->persist($setting);
        $this->em->flush($setting);

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onUpdate', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($setting, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals([], $this->mockedFieldType->events[0]['data']);
        $this->assertEquals($setting->getData(), $this->mockedFieldType->events[0]['new_data']);

        // Update Setting
        $this->mockedFieldType->events = [];
        $setting->setData(['f2' => 'updated']);
        $this->em->flush($setting);

        $this->assertCount(1, $this->mockedFieldType->events);
        $this->assertEquals('onUpdate', $this->mockedFieldType->events[0]['event']);
        $this->assertEquals($setting, $this->mockedFieldType->events[0]['fieldablecontent']);
        $this->assertEquals(['f2' => 'foo'], $this->mockedFieldType->events[0]['data']);
        $this->assertEquals($setting->getData(), $this->mockedFieldType->events[0]['new_data']);
    }
}
