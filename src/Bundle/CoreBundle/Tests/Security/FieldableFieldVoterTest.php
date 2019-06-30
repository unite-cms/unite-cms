<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Tests\SecurityVoterTestCase;

class FieldableFieldVoterTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain;

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var ContentType
     */
    protected $contentType;

    /**
     * @var SettingType
     */
    protected $settingType;

    public function setUp()
    {
        parent::setUp();

        $this->domain = new Domain();
        $this->domain->setOrganization($this->org2)->setId(1);

        $this->contentType = new ContentType();
        $this->contentType->setTitle('CT')->setIdentifier('ct');
        $this->contentType->setDomain($this->domain);

        $f1 = new ContentTypeField();
        $f1->setTitle('F1')->setIdentifier('f1')->setType('text');
        $this->contentType->addField($f1);

        $f2 = new ContentTypeField();
        $f2->setTitle('F2')->setIdentifier('f2')->setPermissions([
            FieldableFieldVoter::LIST => 'true',
            FieldableFieldVoter::VIEW => 'true',
            FieldableFieldVoter::UPDATE => 'false',
        ])->setType('text');
        $this->contentType->addField($f2);

        $f3 = new ContentTypeField();
        $f3->setTitle('F3')->setIdentifier('f3')->setPermissions([
            FieldableFieldVoter::LIST => 'true',
            FieldableFieldVoter::VIEW => 'false',
            FieldableFieldVoter::UPDATE => 'true',
        ])->setType('text');
        $this->contentType->addField($f3);

        $f4 = new ContentTypeField();
        $f4->setTitle('F4')->setIdentifier('f4')->setPermissions([
            FieldableFieldVoter::LIST => 'false',
            FieldableFieldVoter::VIEW => 'false',
            FieldableFieldVoter::UPDATE => 'false',
        ])->setType('text');
        $this->contentType->addField($f4);

        $f5 = new ContentTypeField();
        $f5->setTitle('F5')->setIdentifier('f5')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ])->setType('text');
        $this->contentType->addField($f5);

        $f6 = new ContentTypeField();
        $f6->setTitle('F6')->setIdentifier('f6')->setPermissions([
            FieldableFieldVoter::LIST => 'false',
            FieldableFieldVoter::VIEW => 'true',
            FieldableFieldVoter::UPDATE => 'true',
        ])->setType('text');
        $this->contentType->addField($f6);

        $this->content = new Content();
        $this->content->setContentType($this->contentType);

        $this->settingType = new SettingType();
        $this->settingType->setTitle('ST')->setIdentifier('st');
        $this->settingType->setDomain($this->domain);
        $sf1 = new SettingTypeField();
        $sf1->setTitle('F1')->setIdentifier('f1')->setType('text')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ]);
        $this->settingType->addField($sf1);

        $mf1 = new DomainMemberTypeField();
        $mf1->setTitle('F1')->setIdentifier('f1')->setType('text')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ]);
        $this->domain->getDomainMemberTypes()->get('editor')->addField($mf1);

        $admin = new User();
        $admin->setRoles([User::ROLE_USER])->setName('Admin');
        $adminMember = new OrganizationMember();
        $adminMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $adminDomainMember = new DomainMember();
        $adminDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('editor'));
        $admin->addOrganization($adminMember);
        $admin->addDomain($adminDomainMember);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', $admin->getRoles());
    }

    public function testCRUDActions()
    {

        $dm = static::$container->get('security.authorization_checker');
        $subjectF1 = new FieldableFieldContent($this->contentType->getFields()->get('f1'), $this->content);
        $subjectF2 = new FieldableFieldContent($this->contentType->getFields()->get('f2'), $this->content);
        $subjectF3 = new FieldableFieldContent($this->contentType->getFields()->get('f3'), $this->content);
        $subjectF4 = new FieldableFieldContent($this->contentType->getFields()->get('f4'), $this->content);
        $subjectF5 = new FieldableFieldContent($this->contentType->getFields()->get('f5'), $this->content);
        $subjectF6 = new FieldableFieldContent($this->contentType->getFields()->get('f6'), $this->content);

        $subjectSF1 = new FieldableFieldContent($this->settingType->getFields()->get('f1'), $this->settingType->getSetting());

        $member = $this->u['domain_admin']->getUser()->getDomainMembers($this->domain)[0];
        $subjectMF1 = new FieldableFieldContent($member->getDomainMemberType()->getFields()->first(), $member);

        // Platform admins can preform all field actions.
        static::$container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF2->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF2));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF2));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF3->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF3));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF3));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF4->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF4));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF4));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF5->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF6->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF6));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF6));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectSF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectMF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));

        // Organization admins can preform all field actions on their organization domain's content.
        static::$container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF2->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF2));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF2));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF3->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF3));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF3));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF4->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF4));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF4));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF5->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF6->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF6));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF6));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectSF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectMF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));

        // All other users can preform the actions they have access to.
        static::$container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF1->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF2->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF2));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF2));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF3->getField()));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF3));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF3));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::LIST], $subjectF4->getField()));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF4));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF4));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectF5->getField()));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::LIST], $subjectF6->getField()));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF6));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF6));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectSF1->getField()));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::LIST], $subjectMF1->getField()));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));

        $this->content->setData(['f1' => 'B']);
        $subjectSF1->getContent()->setData(['f1' => 'B']);
        $subjectMF1->getContent()->setData(['f1' => 'B']);

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));

        $this->content->setData(['f1' => 'A']);
        $subjectSF1->getContent()->setData(['f1' => 'A']);
        $subjectMF1->getContent()->setData(['f1' => 'A']);

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertTrue($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertTrue($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));

        $this->content->setData(['f1' => 'C']);
        $subjectSF1->getContent()->setData(['f1' => 'C']);
        $subjectMF1->getContent()->setData(['f1' => 'C']);

        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectF5));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectF5));

        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectSF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectSF1));

        $this->assertFalse($dm->isGranted([FieldableFieldVoter::VIEW], $subjectMF1));
        $this->assertFalse($dm->isGranted([FieldableFieldVoter::UPDATE], $subjectMF1));
    }

    public function testFieldableFormAlteration() {

        $builder = static::$container->get('unite.cms.fieldable_form_builder');
        $member = $this->u['domain_admin']->getUser()->getDomainMembers($this->domain)[0];

        static::$container->get('security.token_storage')->setToken($this->u['platform']);
        $keys = array_keys($builder->createForm($this->contentType, $this->content)->all());
        $this->assertEquals(['f1', 'f2', 'f3', 'f4', 'f5', 'f6'], $keys);

        $keys = array_keys($builder->createForm($this->settingType, $this->settingType->getSetting())->all());
        $this->assertEquals(['f1'], $keys);

        $keys = array_keys($builder->createForm($member->getDomainMemberType(), $member)->all());
        $this->assertEquals(['f1'], $keys);

        static::$container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $keys = array_keys($builder->createForm($this->contentType, $this->content)->all());
        $this->assertEquals(['f1', 'f3'], $keys);

        $keys = array_keys($builder->createForm($this->settingType, $this->settingType->getSetting())->all());
        $this->assertEquals([], $keys);

        $keys = array_keys($builder->createForm($member->getDomainMemberType(), $member)->all());
        $this->assertEquals([], $keys);


        $this->content->setData(['f1' => 'A']);
        $this->settingType->getSetting()->setData(['f1' => 'A']);
        $member->setData(['f1' => 'A']);

        $keys = array_keys($builder->createForm($this->contentType, $this->content)->all());
        $this->assertEquals(['f1', 'f3', 'f5'], $keys);

        $keys = array_keys($builder->createForm($this->settingType, $this->settingType->getSetting())->all());
        $this->assertEquals(['f1'], $keys);

        $keys = array_keys($builder->createForm($member->getDomainMemberType(), $member)->all());
        $this->assertEquals(['f1'], $keys);

        $this->content->setData(['f1' => 'B']);
        $this->settingType->getSetting()->setData(['f1' => 'B']);
        $member->setData(['f1' => 'B']);

        $keys = array_keys($builder->createForm($this->contentType, $this->content)->all());
        $this->assertEquals(['f1', 'f3'], $keys);

        $keys = array_keys($builder->createForm($this->settingType, $this->settingType->getSetting())->all());
        $this->assertEquals([], $keys);

        $keys = array_keys($builder->createForm($member->getDomainMemberType(), $member)->all());
        $this->assertEquals([], $keys);

        $this->content->setData(['f1' => 'C']);
        $this->settingType->getSetting()->setData(['f1' => 'C']);
        $member->setData(['f1' => 'C']);

        $keys = array_keys($builder->createForm($this->contentType, $this->content)->all());
        $this->assertEquals(['f1', 'f3'], $keys);

        $keys = array_keys($builder->createForm($this->settingType, $this->settingType->getSetting())->all());
        $this->assertEquals([], $keys);

        $keys = array_keys($builder->createForm($member->getDomainMemberType(), $member)->all());
        $this->assertEquals([], $keys);
    }
}
