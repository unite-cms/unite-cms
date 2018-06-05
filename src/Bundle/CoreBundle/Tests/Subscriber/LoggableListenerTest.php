<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 01.06.18
 * Time: 15:44
 */

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Subscriber\LoggableListener;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class LoggableListenerTest extends DatabaseAwareTestCase
{
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

        $this->domain = new Domain();
        $this->domain->setIdentifier('domain')->setTitle('Domain')->setOrganization($org);
        $this->em->persist($this->domain);

        $ct1 = new ContentType();
        $ct1->setIdentifier('ct1')->setTitle('CT 1');
        $this->domain->addContentType($ct1);
        $this->em->persist($ct1);

        $st1 = new SettingType();
        $st1->setIdentifier('st1')->setTitle('ST 1');
        $this->domain->addSettingType($st1);
        $this->em->persist($st1);

        $this->em->flush();

        $dmt1Field = new DomainMemberTypeField();
        $dmt1Field->setIdentifier('field')->setTitle('Field')->setType('text')->setDomainMemberType($this->domain->getDomainMemberTypes()->first());

        $ct1Field = new ContentTypeField();
        $ct1Field->setIdentifier('field')->setTitle('Field')->setType('text')->setContentType($ct1);

        $st1Field = new SettingTypeField();
        $st1Field->setIdentifier('field')->setTitle('Field')->setType('text')->setSettingType($st1);

        $this->em->persist($dmt1Field);
        $this->em->persist($st1Field);
        $this->em->persist($ct1Field);

        $this->em->flush();
    }

    /**
     * @param $object
     * @param $action
     * @param $data
     */
    protected function assertLastLogEntry($object, $action, $data) {
        $logEntries = $this->em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($object);
        $this->assertGreaterThan(0, count($logEntries));
        $logEntry = $logEntries[0];
        $this->assertEquals($action, $logEntry->getAction());

        if($data) {
            $this->assertEquals($data, $logEntry->getData()['data']);
        } else {
            $this->assertEmpty($logEntry->getData());
        }
    }

    public function testContentLogger() {

        $content = new Content();
        $content->setData(['field' => 'original data'])->setContentType($this->domain->getContentTypes()->first());
        $this->em->persist($content);
        $this->em->flush();

        $contentId = $content->getId();

        $this->assertLastLogEntry($content, LoggableListener::ACTION_CREATE, ['field' => 'original data']);

        $content->setData(['field' => 'new data']);
        $this->em->flush();

        $this->assertLastLogEntry($content, LoggableListener::ACTION_UPDATE, ['field' => 'new data']);

        $this->em->remove($content);
        $this->em->flush();
        $this->em->clear();

        $this->assertLastLogEntry($content, LoggableListener::ACTION_REMOVE, null);

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($contentId);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $content->recoverDeleted();
        $this->em->flush();
        $this->em->clear();

        $this->assertLastLogEntry($content, LoggableListener::ACTION_RECOVER, ['field' => 'new data']);

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($contentId);
        $this->em->remove($content);
        $this->em->flush();

        $this->em->remove($content);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($content));
    }

    public function testSettingLogger() {

        $setting = new Setting();
        $setting->setData(['field' => 'original data'])->setSettingType($this->domain->getSettingTypes()->first());
        $this->em->persist($setting);
        $this->em->flush();

        $this->assertLastLogEntry($setting, LoggableListener::ACTION_CREATE, ['field' => 'original data']);

        $setting->setData(['field' => 'new data']);
        $this->em->flush();

        $this->assertLastLogEntry($setting, LoggableListener::ACTION_UPDATE, ['field' => 'new data']);

        $this->em->remove($setting);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($setting));
    }

    public function testDomainMemberLogger() {

        $member = new DomainMember();
        $member->setData(['field' => 'original data'])->setDomainMemberType($this->domain->getDomainMemberTypes()->first())->setDomain($this->domain);
        $this->em->persist($member);
        $this->em->flush();

        $this->assertLastLogEntry($member, LoggableListener::ACTION_CREATE, ['field' => 'original data']);

        $member->setData(['field' => 'new data']);
        $this->em->flush();

        $this->assertLastLogEntry($member, LoggableListener::ACTION_UPDATE, ['field' => 'new data']);

        $this->em->remove($member);
        $this->em->flush();

        $this->assertCount(0, $this->em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($member));
    }
}