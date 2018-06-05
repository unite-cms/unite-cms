<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 01.06.18
 * Time: 15:44
 */

namespace UniteCMS\CoreBundle\Tests\Subscriber;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DeleteFieldableContentSubscriberTest extends DatabaseAwareTestCase
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

        $ct2 = new ContentType();
        $ct2->setIdentifier('ct2')->setTitle('CT 2');
        $this->domain->addContentType($ct2);
        $this->em->persist($ct2);

        $st1 = new SettingType();
        $st1->setIdentifier('st1')->setTitle('ST 1');
        $this->domain->addSettingType($st1);
        $this->em->persist($st1);

        $st2 = new SettingType();
        $st2->setIdentifier('st2')->setTitle('ST 2');
        $this->domain->addSettingType($st2);
        $this->em->persist($st2);

        for($i = 1; $i <= 5; $i++) {
            $content1 = new Content();
            $content1->setContentType($ct1);
            $this->em->persist($content1);

            $content2 = new Content();
            $content2->setContentType($ct2);
            $this->em->persist($content2);

            $setting1 = new Setting();
            $setting1->setSettingType($st1);
            $this->em->persist($setting1);

            $setting2 = new Setting();
            $setting2->setSettingType($st2);
            $this->em->persist($setting2);

            $user = new User();
            $user->setName("User $i")->setEmail("user$i@example.com")->setPassword('password');
            $this->em->persist($user);

            $domainMember = new DomainMember();
            $domainMember->setAccessor($user)->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->first());
            $this->em->persist($domainMember);
        }

        $this->em->flush();
        $this->em->refresh($this->domain);
    }

    public function testAutoDeleteContentType()
    {

        // We have 10 CREATE content log entries.
        $this->assertEquals(
            10,
            $this->em->getRepository('GedmoLoggable:LogEntry')->count(['objectClass' => Content::class])
        );

        // Delete content type1
        $this->em->remove($this->domain->getContentTypes()->get('ct1'));
        $this->em->flush();

        // We have 5 CREATE content log entries remaining
        $this->assertEquals(
            5,
            $this->em->getRepository('GedmoLoggable:LogEntry')->count(['objectClass' => Content::class])
        );
    }

    public function testAutoDeleteSettingType()
    {
        // We have 10 CREATE setting log entries.
        $this->assertEquals(
            10,
            $this->em->getRepository('GedmoLoggable:LogEntry')->count(['objectClass' => Setting::class])
        );

        // Delete setting type 1
        $this->em->remove($this->domain->getSettingTypes()->get('st1'));
        $this->em->flush();

        // We have 5 CREATE setting log entries remaining
        $this->assertEquals(
            5,
            $this->em->getRepository('GedmoLoggable:LogEntry')->count(['objectClass' => Setting::class])
        );
    }

    public function testAutoDeleteDomain() {

        // We have 5 CREATE member log entries
        $this->assertEquals(5, $this->em->getRepository('GedmoLoggable:LogEntry')->count(['objectClass' => DomainMember::class]));

        // When deleting the domain, auto delete of all other log entries should be triggered
        $this->em->remove($this->domain);
        $this->em->flush();

        // Deleting the domain should trigger deleting of all log entries.
        $this->assertEquals(0, $this->em->getRepository('GedmoLoggable:LogEntry')->count([]));
    }
}