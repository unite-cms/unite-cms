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
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DeleteFieldableFieldSubscriberTest extends DatabaseAwareTestCase
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

        $ct = new ContentType();
        $ct->setIdentifier('ct')->setTitle('CT');
        $this->domain->addContentType($ct);

        $ctField1 = new ContentTypeField();
        $ctField1->setIdentifier('f1')->setTitle('F1')->setType('text')->setContentType($ct);

        $ctField2 = new ContentTypeField();
        $ctField2->setIdentifier('f2')->setTitle('F2')->setType('text')->setContentType($ct);

        $st = new SettingType();
        $st->setIdentifier('st')->setTitle('ST');
        $this->domain->addSettingType($st);

        $stField1 = new SettingTypeField();
        $stField1->setIdentifier('f1')->setTitle('F1')->setType('text')->setSettingType($st);

        $stField2 = new SettingTypeField();
        $stField2->setIdentifier('f2')->setTitle('F2')->setType('text')->setSettingType($st);

        $mt = $this->domain->getDomainMemberTypes()->first();
        $mt->setIdentifier('mt')->setTitle('MT');

        $mtField1 = new DomainMemberTypeField();
        $mtField1->setIdentifier('f1')->setTitle('F1')->setType('text')->setDomainMemberType($mt);

        $mtField2 = new DomainMemberTypeField();
        $mtField2->setIdentifier('f2')->setTitle('F2')->setType('text')->setDomainMemberType($mt);

        $this->em->persist($ct);
        $this->em->persist($st);

        for($i = 1; $i <= 2; $i++) {
            $content = new Content();
            $content->setContentType($ct)->setData(['f1' => 'foo', 'f2' => 'baa']);
            $this->em->persist($content);

            $setting = new Setting();
            $setting->setSettingType($st)->setData(['f1' => 'foo', 'f2' => 'baa']);;
            $this->em->persist($setting);


            $user = new User();
            $user->setName("User $i")->setEmail("user$i@example.com")->setPassword('password');
            $this->em->persist($user);

            $domainMember = new DomainMember();
            $domainMember->setAccessor($user)->setDomain($this->domain)->setDomainMemberType($mt)->setData(['f1' => 'foo', 'f2' => 'baa']);;
            $this->em->persist($domainMember);
        }

        $this->em->flush();

        // Delete the 2nd content.
        $this->em->remove($content);
        $this->em->flush();

        $this->em->refresh($this->domain);
    }

    public function testDeleteFieldShouldDeleteFieldContent()
    {
        // Before deleting, all content, setting and member objects contain the full data set.
        $data = ['f1' => 'foo', 'f2' => 'baa'];

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findAll();
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $this->assertCount(2, $content);
        $this->assertEquals($data, $content[0]->getData());
        $this->assertEquals($data, $content[1]->getData());

        $setting = $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll();
        $this->assertCount(2, $setting);
        $this->assertEquals($data, $setting[0]->getData());
        $this->assertEquals($data, $setting[1]->getData());

        $member = $this->em->getRepository('UniteCMSCoreBundle:DomainMember')->findAll();
        $this->assertCount(2, $member);
        $this->assertEquals($data, $member[0]->getData());
        $this->assertEquals($data, $member[1]->getData());

        // Delete f1 fields.
        $this->domain->getContentTypes()->first()->getFields()->remove('f1');
        $this->domain->getSettingTypes()->first()->getFields()->remove('f1');
        $this->domain->getDomainMemberTypes()->first()->getFields()->remove('f1');

        // On flush, the field content should get deleted.
        $this->em->flush();

        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $this->em->refresh($content[0]);
        $this->em->refresh($content[1]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        $this->em->refresh($setting[0]);
        $this->em->refresh($setting[1]);

        $this->em->refresh($member[0]);
        $this->em->refresh($member[1]);

        // f1 value was deleted from all objects.
        $data = ['f2' => 'baa'];

        $this->assertEquals($data, $content[0]->getData());
        $this->assertEquals($data, $content[1]->getData());

        $this->assertEquals($data, $setting[0]->getData());
        $this->assertEquals($data, $setting[1]->getData());

        $this->assertEquals($data, $member[0]->getData());
        $this->assertEquals($data, $member[1]->getData());
    }
}