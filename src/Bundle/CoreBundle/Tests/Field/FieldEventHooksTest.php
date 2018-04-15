<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 15:41
 */

namespace UniteCMS\CoreBundle\Tests\Field;


use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class FieldEventHooksTest extends DatabaseAwareTestCase
{
    private $domainConfig = '
    {
        "title": "Domain",
        "identifier": "d",
        "content_types": [
            {
                "title": "CT 1",
                "identifier": "ct1",
                "fields": [
                    {
                        "title": "Test",
                        "identifier": "test",
                        "type": "testeventhook"
                    }
                ]
            }
        ],
        "setting_types": [
            {
                "title": "ST 1",
                "identifier": "st1",
                "fields": [
                    {
                        "title": "Test",
                        "identifier": "test",
                        "type": "testeventhook"
                    }
                ]
            }
        ]
    }';

    /**
     * @var Domain $domain
     */
    private $domain;

    public function setUp()
    {
        parent::setUp();

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');
        $this->em->persist($org);
        $this->em->flush($org);

        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfig);
        $this->domain->setOrganization($org);
        $this->em->persist($this->domain);
        $this->em->flush($this->domain);
    }

    public function testContentCreateEvent() {

        $this->container->get('unite.cms.field_type_manager')->registerFieldType(new class extends FieldType {
            const TYPE = 'testeventhook';
            public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {
                $data[$field->getIdentifier()] .= '_modified';
            }
        });

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first());
        $content->setData(['test' => 'foo']);

        $this->em->persist($content);

        // Make sure, that the create event could modify content before saving.
        $this->assertEquals(['test' => 'foo_modified'], $content->getData());

        $this->em->flush();
        $this->em->refresh($content);

        // Make sure, that the create event could modify content before saving.
        $this->assertEquals(['test' => 'foo_modified'], $content->getData());
    }

    public function testContentUpdateEvent() {

        $this->container->get('unite.cms.field_type_manager')->registerFieldType(new class extends FieldType {
            const TYPE = 'testeventhook';
            public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
                $data[$field->getIdentifier()] .= '_' . $old_data[$field->getIdentifier()] . '_modified';
            }
        });

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first());
        $content->setData(['test' => 'foo']);

        $this->em->persist($content);

        $this->em->flush();
        $this->em->refresh($content);

        // Make sure, that the create event did not trigger anything.
        $this->assertEquals(['test' => 'foo'], $content->getData());

        $content->setData(['test' => 'baa']);

        $this->em->flush($content);
        $this->em->refresh($content);

        // Make sure, that the create event could modify content before saving.
        $this->assertEquals(['test' => 'baa_foo_modified'], $content->getData());
    }

    public function testContentDeleteEvent() {

        $mock = new class extends FieldType {
            public $invokeSoftDeleteCounter = 0;
            public $invokeHardDeleteCounter = 0;
            const TYPE = 'testeventhook';
            public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                $this->invokeSoftDeleteCounter++;
            }
            public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                $this->invokeHardDeleteCounter++;
            }
        };

        $this->container->get('unite.cms.field_type_manager')->registerFieldType($mock);

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first());
        $content->setData(['test' => 'foo']);

        $this->em->persist($content);

        $this->em->flush();
        $this->em->refresh($content);

        $this->em->remove($content);
        $this->em->flush();

        // softDelete should be invoked.
        $this->assertEquals(1, $mock->invokeSoftDeleteCounter);

        // Remove it for real.
        $this->em->getFilters()->disable('gedmo_softdeleteable');

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([
            'contentType' => $this->domain->getContentTypes()->first(),
        ]);

        $this->em->remove($content);
        $this->em->flush();
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // softDelete and hardDelete should be invoked.
        $this->assertEquals(1, $mock->invokeSoftDeleteCounter);
        $this->assertEquals(1, $mock->invokeHardDeleteCounter);
    }

    public function testSettingUpdateEvent() {

        $mock = new class extends FieldType {
            public $invokeCreateCounter = 0;
            public $invokeSoftDeleteCounter = 0;
            public $invokeHardDeleteCounter = 0;
            const TYPE = 'testeventhook';

            public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                $this->invokeCreateCounter++;
            }
            public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                $this->invokeSoftDeleteCounter++;
            }
            public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                $this->invokeHardDeleteCounter++;
            }
            public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
                $data[$field->getIdentifier()] .= '_' . $old_data[$field->getIdentifier()] . '_modified';
            }
        };

        $this->container->get('unite.cms.field_type_manager')->registerFieldType($mock);

        $setting = new Setting();
        $setting->setSettingType($this->domain->getSettingTypes()->first());
        $setting->setData(['test' => 'foo']);

        $this->em->persist($setting);

        $this->em->flush();
        $this->em->refresh($setting);


        $setting->setData(['test' => 'baa']);

        $this->em->flush($setting);
        $this->em->refresh($setting);

        // Make sure, that the create event could modify setting before saving.
        $this->assertEquals(['test' => 'baa_foo_modified'], $setting->getData());

        $this->em->remove($setting);
        $this->em->flush();

        // make sure, that only onDelete was called for settings.
        $this->assertEquals(0, $mock->invokeCreateCounter);
        $this->assertEquals(0, $mock->invokeSoftDeleteCounter);
        $this->assertEquals(0, $mock->invokeHardDeleteCounter);

    }
}
