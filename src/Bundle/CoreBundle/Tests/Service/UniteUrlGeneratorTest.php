<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 13:09
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Service\UniteCMSRouter;

class UniteUrlGeneratorTest extends KernelTestCase
{
    /**
     * @var Router $router
     */
    private $router;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var ContentType $contentType
     */
    private $contentType;

    /**
     * @var SettingType $settingType
     */
    private $settingType;

    /**
     * @var DomainMemberType $domainMemberType
     */
    private $domainMemberType;

    /**
     * @var Content $content
     */
    private $content;

    /**
     * @var Setting $setting
     */
    private $setting;

    /**
     * @var DomainMember $domainMember
     */
    private $domainMember;

    public function setUp()
    {
        self::bootKernel();
        $this->router = self::$kernel->getContainer()->get('router');

        $this->organization = new Organization();
        $this->organization->setIdentifier('org1_org1');

        $this->domain = new Domain();
        $this->domain->setIdentifier('domain_domain');
        $this->domain->setOrganization($this->organization);

        $this->contentType = new ContentType();
        $this->contentType->setIdentifier('ct_1');
        $this->contentType->setDomain($this->domain);

        $this->settingType = new SettingType();
        $this->settingType->setIdentifier('st_1');
        $this->settingType->setDomain($this->domain);

        $this->domainMemberType = new DomainMemberType();
        $this->domainMemberType->setIdentifier('dmt_1');
        $this->domainMemberType->setDomain($this->domain);

        $this->content = new Content();
        $this->content->setContentType($this->contentType);
        $id_reflection = new \ReflectionProperty(Content::class, 'id');
        $id_reflection->setAccessible(true);
        $id_reflection->setValue($this->content, 'xxx-yyy-zzz');

        $this->setting = new Setting();
        $this->setting->setSettingType($this->settingType);
        $id_reflection = new \ReflectionProperty(Setting::class, 'id');
        $id_reflection->setAccessible(true);
        $id_reflection->setValue($this->setting, 'xxx-yyy-zzz');

        $this->domainMember = new DomainMember();
        $this->domainMember->setDomainMemberType($this->domainMemberType);
        $id_reflection = new \ReflectionProperty(DomainMember::class, 'id');
        $id_reflection->setAccessible(true);
        $id_reflection->setValue($this->domainMember, 'xxx-yyy-zzz');
    }

    public function testServiceDecorator() {
        $this->assertInstanceOf(UniteCMSRouter::class, $this->router);
    }

    public function testGeneratingOrganizationUrls() {
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1']),
            $this->router->generate('unitecms_core_domain_index', $this->organization)
        );

        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1']),
            $this->router->generate('unitecms_core_domain_index', ['organization' => $this->organization])
        );

        // Make sure, that urls are absolute per default
        $this->assertEquals($this->router->generate('unitecms_core_domain_index', $this->organization, Router::ABSOLUTE_URL), $this->router->generate('unitecms_core_domain_index', $this->organization));

        // Make sure, that custom routing also works in the twig extension.
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1']),
            self::$container->get('twig.extension.routing')->getUrl('unitecms_core_domain_index', ['organization' => $this->organization])
        );

        // Make sure, that absolute url cannot be overriden
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1'], Router::ABSOLUTE_URL),
            self::$container->get('twig.extension.routing')->getPath('unitecms_core_domain_index', ['organization' => $this->organization])
        );
    }

    public function testGeneratingDomainUrls() {
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_view', ['organization' => 'org1-org1', 'domain' => 'domain-domain']),
            $this->router->generate('unitecms_core_domain_view', $this->domain)
        );

        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_view', ['organization' => 'org1-org1', 'domain' => 'domain-domain']),
            $this->router->generate('unitecms_core_domain_view', ['domain' => $this->domain])
        );

        // Also a mixture of entities and parameters is possible.
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_view', ['organization' => 'org1-org1', 'domain' => 'domain-domain']),
            $this->router->generate('unitecms_core_domain_view', ['organization' => $this->organization, 'domain' => 'domain-domain'])
        );

        // When passing an entity, that can find more parameters than required, only the required ones should be used.
        $this->assertEquals(
            $this->router->generate('unitecms_core_domain_index', ['organization' => 'org1-org1']),
            $this->router->generate('unitecms_core_domain_index', $this->domain)
        );
    }

    public function testGeneratingContentUrls()
    {
        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'all']
            ),
            $this->router->generate('unitecms_core_content_index', $this->contentType)
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'foo']
            ),
            $this->router->generate('unitecms_core_content_index', [$this->contentType, 'view' => 'foo'])
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'all']
            ),
            $this->router->generate('unitecms_core_content_index', $this->content)
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'foo']
            ),
            $this->router->generate('unitecms_core_content_index', [$this->content, 'view' => 'foo'])
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'foo']
            ),
            $this->router->generate('unitecms_core_content_index', ['content' => $this->content, 'view' => 'foo'])
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'view' => 'foo']
            ),
            $this->router->generate('unitecms_core_content_index', ['content_type' => $this->content, 'view' => 'foo'])
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_content_update',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'content_type' => 'ct-1', 'content' => $this->content->getId()]
            ),
            $this->router->generate('unitecms_core_content_update', $this->content)
        );
    }

    public function testGeneratingSettingUrls()
    {
        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_setting_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'setting_type' => 'st-1']
            ),
            $this->router->generate('unitecms_core_setting_index', $this->settingType)
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_setting_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'setting_type' => 'st-1']
            ),
            $this->router->generate('unitecms_core_setting_index', $this->setting)
        );
    }

    public function testGeneratingDomainMemberUrls()
    {
        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_domainmember_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'member_type' => 'dmt-1']
            ),
            $this->router->generate('unitecms_core_domainmember_index', $this->domainMemberType)
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_domainmember_index',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'member_type' => 'dmt-1']
            ),
            $this->router->generate('unitecms_core_domainmember_index', $this->domainMember)
        );

        $this->assertEquals(
            $this->router->generate(
                'unitecms_core_domainmember_update',
                ['organization' => 'org1-org1', 'domain' => 'domain-domain', 'member_type' => 'dmt-1', 'member' => $this->domainMember->getId()]
            ),
            $this->router->generate('unitecms_core_domainmember_update', $this->domainMember)
        );
    }
}