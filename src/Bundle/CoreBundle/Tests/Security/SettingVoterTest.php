<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Tests\SecurityVoterTestCase;

class SettingVoterTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain1;

    /**
     * @var Domain
     */
    protected $domain2;

    /**
     * @var Setting
     */
    protected $setting1;

    /**
     * @var Setting
     */
    protected $setting2;

    /**
     * @var SettingType
     */
    protected $settingType1;

    /**
     * @var SettingType
     */
    protected $settingType2;

    public function setUp()
    {
        parent::setUp();

        $this->domain1 = new Domain();
        $this->domain1->setTitle('Domain1')->setIdentifier('domain1')->setOrganization($this->org1)->setId(1);

        $this->domain2 = new Domain();
        $this->domain2->setTitle('Domain2')->setIdentifier('domain2')->setOrganization($this->org2)->setId(2);

        $this->settingType1 = new SettingType();
        $this->settingType1->setDomain($this->domain1);
        $p1 = $this->settingType1->getPermissions();
        $this->settingType1->setPermissions($p1);

        $this->settingType2 = new SettingType();
        $this->settingType2->setDomain($this->domain2);

        $this->setting1 = new Setting();
        $this->setting1->setSettingType($this->settingType1);

        $this->setting2 = new Setting();
        $this->setting2->setSettingType($this->settingType2);

        $admin = new User();
        $admin->setRoles([User::ROLE_USER]);
        $adminMember = new OrganizationMember();
        $adminMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $adminDomainMember = new DomainMember();
        $adminDomainMember->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('editor'))->setDomain($this->domain1);
        $admin->addOrganization($adminMember);
        $admin->addDomain($adminDomainMember);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', $admin->getRoles());

        $user = new User();
        $user->setRoles([User::ROLE_USER]);
        $userMember = new OrganizationMember();
        $userMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $userDomainMember = new DomainMember();
        $userDomainMember->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('viewer'))->setDomain($this->domain1);
        $user->addOrganization($userMember);
        $user->addDomain($userDomainMember);
        $this->u['domain_editor'] = new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());
    }

    public function testFallbackForNotSupportedArguments() {
        $voter = new SettingVoter();

        $invalidUser = new class {
            public function __toString() { return 'any'; }
        };

        // Try with invalid token.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            new UsernamePasswordToken($invalidUser, '', 'main', []),
            $this->settingType1,
            [SettingVoter::VIEW]
        ));

        // Try with invalid subject.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            $this->u['domain_admin'],
            (object)[],
            [SettingVoter::VIEW]
        ));

        // Try with invalid attribute.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            $this->u['domain_admin'],
            $this->settingType1,
            ['any']
        ));
    }

    public function testCRUDActions()
    {

        $dm = static::$container->get('security.authorization_checker');

        // Platform admins can preform all setting actions.
        static::$container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertTrue($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        // Organization admins can preform all setting actions on their organization domain's setting.
        static::$container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertTrue($dm->isGranted([SettingVoter::UPDATE], $this->setting2));

        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        // All other users can preform the actions they have access to.
        static::$container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertTrue($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting2));

        static::$container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([SettingVoter::VIEW], $this->setting1));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting1));

        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting2));

        // Anonymous user have only access to setting if it is granted
        static::$container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([SettingVoter::VIEW], $this->setting2));
        $this->assertFalse($dm->isGranted([SettingVoter::UPDATE], $this->setting2));
    }
}
