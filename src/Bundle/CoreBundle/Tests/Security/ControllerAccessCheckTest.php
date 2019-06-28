<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class ControllerAccessCheckTest extends DatabaseAwareTestCase
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var User[]
     */
    private $users = [];

    /**
     * @var Content
     */
    private $content1;

    /**
     * @var Setting
     */
    private $setting1;

    /**
     * @var Invitation
     */
    private $invite1;

    /**
     * @var ApiKey $apiKey1
     */
    private $apiKey1;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller access check domain",
    "identifier": "access_check", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1",
        "preview": {
          "url": "https://example.com",
          "query": "query { type }"
        }
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1",
        "preview": {
          "url": "https://example.com",
          "query": "query { type }"
        }
      }
    ]
  }';

    protected $databaseStrategy = DatabaseAwareTestCase::STRATEGY_RECREATE;

    protected $loginUrl = null;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);
        $this->client->disableReboot();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test controller access check')->setIdentifier('access_check');
        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);
        $this->domain->addPermission(DomainVoter::UPDATE, 'member.type == "editor"');

        foreach ($this->domain->getDomainMemberTypes() as $domainMemberType) {
            $domainMemberType->addPermission(DomainMemberVoter::LIST, 'member.type == "editor"');
            $domainMemberType->addPermission(DomainMemberVoter::VIEW, 'member.type == "editor"');
            $domainMemberType->addPermission(DomainMemberVoter::CREATE, 'member.type == "editor"');
            $domainMemberType->addPermission(DomainMemberVoter::UPDATE, 'member.type == "editor"');
            $domainMemberType->addPermission(DomainMemberVoter::DELETE, 'member.type == "editor"');
        }

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);

        $this->users['domain_editor'] = new User();
        $this->users['domain_editor']
            ->setEmail('domain_editor@example.com')
            ->setName('Domain Editor')
            ->setRoles([User::ROLE_USER])
            ->setPassword('XXX');

        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('editor'));
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);
        $this->users['domain_editor']->addDomain($domainEditorDomainMember);

        $this->users['domain_viewer'] = new User();
        $this->users['domain_viewer']
            ->setEmail('domain_viewer@example.com')
            ->setName('Domain Viewer')
            ->setRoles([User::ROLE_USER])
            ->setPassword('XXX');

        $domainViewerOrgMember = new OrganizationMember();
        $domainViewerOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainViewerDomainMember = new DomainMember();
        $domainViewerDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('viewer'));
        $this->users['domain_viewer']->addOrganization($domainViewerOrgMember);
        $this->users['domain_viewer']->addDomain($domainViewerDomainMember);


        $this->users['organization_member'] = new User();
        $this->users['organization_member']
            ->setEmail('organization_member@example.com')
            ->setName('Organization Member')
            ->setRoles([User::ROLE_USER])
            ->setPassword('XXX');

        $orgMemberOrgMember = new OrganizationMember();
        $orgMemberOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $this->users['organization_member']->addOrganization($orgMemberOrgMember);

        $this->users['organization_admin'] = new User();
        $this->users['organization_admin']
            ->setEmail('organization_admin@example.com')
            ->setName('Organization Admin')
            ->setRoles([User::ROLE_USER])
            ->setPassword('XXX');

        $orgAdminOrgMember = new OrganizationMember();
        $orgAdminOrgMember->setRoles([Organization::ROLE_ADMINISTRATOR])->setOrganization($this->organization);
        $this->users['organization_admin']->addOrganization($orgAdminOrgMember);

        $this->users['platform'] = new User();
        $this->users['platform']
            ->setEmail('platform@example.com')
            ->setName('Platform')
            ->setRoles([User::ROLE_PLATFORM_ADMIN])
            ->setPassword('XXX');

        foreach ($this->users as $user) {
            $this->em->persist($user);
        }

        $this->em->flush();

        foreach ($this->users as $user) {
            $this->em->refresh($user);
        }

        // Create Test Content
        $this->content1 = new Content();
        $this->content1->setContentType($this->domain->getContentTypes()->get('ct1'));
        $this->em->persist($this->content1);

        // Create Test Setting
        $this->setting1 = new Setting();
        $this->setting1->setSettingType($this->domain->getSettingTypes()->get('st1'));
        $this->em->persist($this->setting1);

        // Create Test invite
        $this->invite1 = new Invitation();
        $this->invite1->setEmail('invite@example.com')->setDomainMemberType($this->domain->getDomainMemberTypes()->first());
        $this->em->persist($this->invite1);

        // Create Test API Client
        $this->apiKey1 = new ApiKey();
        $this->apiKey1->setOrganization($this->organization);
        $domainEditor = new DomainMember();
        $domainEditor->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('viewer'));
        $this->apiKey1
            ->setName('API Client 1')
            ->setToken('xxx')
            ->addDomain($domainEditor);

        $this->em->persist($this->apiKey1);

        $this->em->flush();
        $this->em->refresh($this->content1);
        $this->em->refresh($this->invite1);
        $this->em->refresh($this->apiKey1);

        $this->loginUrl = static::$container->get('router')->generate('unitecms_core_authentication_login', [], Router::ABSOLUTE_URL);
    }

    private function assertAccess($route, $canAccess, $methods = ['GET'], $parameters = [])
    {
        // Because we don't reboot the kernel on each request, clear all previous created but not persisted entities.
        $this->em->clear();

        foreach ($methods as $method) {
            $this->client->request($method, $route, $parameters);

            if ($canAccess) {

                // Only check redirection if it is redirecting to another route.
                if (!$this->client->getResponse()->isRedirect($route) && !$this->client->getResponse()->isRedirect($route)) {
                    $this->assertFalse($this->client->getResponse()->isRedirect($this->loginUrl));
                }
                $this->assertFalse($this->client->getResponse()->isForbidden());
                $this->assertFalse($this->client->getResponse()->isServerError());
                $this->assertFalse($this->client->getResponse()->isClientError());
            } else {
                $forbidden = ($this->client->getResponse()->isForbidden() || ($this->client->getResponse()->isRedirect($this->loginUrl)));
                $this->assertTrue($forbidden);
            }
        }

        // Check, that all other methods are not allowed (Http 405).
        // This check does not works for the login action, because this action will
        // redirect the user to the invalid route login/ if method is not GET or POST.
        if ($canAccess && $route != $this->loginUrl) {
            $methodsAvailable = ['GET', 'POST', 'PUT', 'DELETE'];
            foreach (array_diff($methodsAvailable, $methods) as $method) {
                $this->client->request($method, $route);
                if (!$this->client->getResponse()->isRedirect()) {
                    $this->assertEquals(
                        405,
                        $this->client->getResponse()
                            ->getStatusCode()
                    );
                }
            }
        }
    }

    private function assertRedirect($route, $destination, $methods = ['GET'])
    {
        foreach ($methods as $method) {
            $this->client->request($method, $route);
            $this->assertTrue($this->client->getResponse()->isRedirect($destination));
        }
    }

    private function checkRoutes($routes, $parameter) {
        foreach($routes as $route => $settings) {

            $url = static::$container->get('router')->generate($route, $parameter);
            $url_parts = explode('?', $url);
            $url = $url_parts[0];

            if(!empty($settings['query'])) {
                $url .= '?' . $settings['query'];
            }

            if(isset($settings['access'])) {
                $this->assertAccess(
                    $url,
                    $settings['access'],
                    $settings['methods'],
                    $settings['params'] ?? []
                );
            } else {
                $redirect = static::$container->get('router')->generate('unitecms_core_index');
                $this->assertRedirect($url, $redirect, $settings['methods']);
            }


        }

    }

    private function login(User $user)
    {

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session = $this->client->getContainer()->get('session');
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testControllerActionAccessForAnonymous()
    {
        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_editor']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];

        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'access' => true, 'methods' => ['GET', 'POST'], 'params' => ['_username' => '', '_password' => ''] ],
            'unitecms_core_profile_resetpassword'           => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_logout'                          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_deletedefinitely'        => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_recover'                 => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_editor']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

    public function testControllerActionAccessForDomainEditor()
    {
        $this->login($this->users['domain_editor']);

        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_editor']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];
        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_editor']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
        ], $parameter);

        $org = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findOneBy(['identifier' => 'access_check']);
        $domain2 = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $domain2->setIdentifier('access_check2')->setTitle('Domain 2')->setOrganization($org);

        $content2 = new Content();
        $content2->setContentType($domain2->getContentTypes()->get('ct1'));
        $this->em->persist($domain2);
        $this->em->persist($content2);
        $this->em->flush();

        $setting2 = new Setting();
        $setting2->setSettingType($domain2->getSettingTypes()->get('st1'));
        $this->em->persist($setting2);
        $this->em->flush();

        $orgMember2 = new OrganizationMember();
        $orgMember2->setOrganization($org);
        $this->em->persist($orgMember2);
        $this->em->flush();

        $member2 = new DomainMember();
        $member2->setDomainMemberType($domain2->getDomainMemberTypes()->first());
        $this->em->persist($member2);
        $this->em->flush();

        $parameter['domain'] = $domain2->getIdentifier();
        $parameter['content'] = $content2->getId();
        $parameter['setting'] = $setting2->getId();
        $parameter['member'] = $orgMember2->getId();

        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $member2->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

    public function testControllerActionAccessForDomainViewer()
    {
        $this->login($this->users['domain_viewer']);
        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_viewer']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];

        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_viewer']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

    public function testControllerActionAccessForOrganizationMember()
    {
        $this->login($this->users['organization_member']);

        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_editor']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];

        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_viewer']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

    public function testControllerActionAccessForOrganizationAdmin()
    {
        $this->login($this->users['organization_admin']);

        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_editor']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];
        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_viewer']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
        ], $parameter);

        $org2 = new Organization();
        $org2->setTitle('Org 2')->setIdentifier('access_check2');
        $domain2 = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $domain2->setIdentifier('access_check2')->setTitle('Domain 2')->setOrganization($org2);

        $content2 = new Content();
        $content2->setContentType($domain2->getContentTypes()->get('ct1'));

        $setting2 = new Setting();
        $setting2->setSettingType($domain2->getSettingTypes()->get('st1'));

        $orgMember2 = new OrganizationMember();
        $orgMember2->setOrganization($org2);

        $member2 = new DomainMember();
        $member2->setDomainMemberType($domain2->getDomainMemberTypes()->first());

        $this->em->persist($org2);
        $this->em->persist($orgMember2);
        $this->em->persist($domain2);
        $this->em->persist($content2);
        $this->em->persist($setting2);
        $this->em->persist($member2);
        $this->em->flush();

        $parameter['organization'] = $org2->getIdentifier();
        $parameter['domain'] = $domain2->getIdentifier();
        $parameter['content'] = $content2->getId();
        $parameter['setting'] = $setting2->getId();
        $parameter['member'] = $orgMember2->getId();

        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => false, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $member2->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => false, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => false, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

    public function testControllerActionAccessForPlatformAdmin()
    {
        $this->login($this->users['platform']);

        $parameter = [
            'organization' => 'access-check',
            'domain' => 'access-check',
            'content_type' => 'ct1',
            'setting_type' => 'st1',
            'view' => 'all',
            'content' => $this->content1->getId(),
            'setting' => $this->setting1->getId(),
            'member' => $this->users['domain_editor']->getOrganizations()->first()->getId(),
            'invite' => $this->invite1->getId(),
            'apiKey' => $this->apiKey1->getId(),
            'member_type' => $this->domain->getDomainMemberTypes()->first()->getIdentifier(),
            'locale' => 'de',
            'version' => 1,
        ];
        $this->checkRoutes([
            'unitecms_core_authentication_login'            => [ 'redirect' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_resetpassword'           => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_resetpasswordconfirm'    => [ 'redirect' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_profile_acceptinvitation'        => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_index'                           => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_profile_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organization_create'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_update'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organization_delete'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_index'          => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organizationuser_update'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_delete'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_createinvite'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationuser_deleteinvite'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_index'        => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_organizationapikey_create'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_update'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_organizationapikey_delete'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_index'                    => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_create'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_view'                     => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domain_update'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domain_delete'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_index'                   => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_create'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_update'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_content_delete'                  => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_translations'            => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_addtranslation'          => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_removetranslation'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_content_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_content_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_index'                   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_setting_preview'                 => [ 'access' => true, 'methods' => ['POST'] ],
            'unitecms_core_setting_revisions'               => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_setting_revisionsrevert'         => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_api'                             => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
            'unitecms_core_graph_api'                       => [ 'access' => false, 'methods' => ['POST'], 'query' => 'token=X' ],
        ], $parameter);

        $parameter['member'] = $this->users['domain_editor']->getDomainMembers($this->domain)[0]->getId();
        $this->checkRoutes([
            'unitecms_core_domainmember_index'              => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_create'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_update'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_revisions'          => [ 'access' => true, 'methods' => ['GET'] ],
            'unitecms_core_domainmember_revisionsrevert'   => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_delete'             => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
            'unitecms_core_domainmember_deleteinvite'       => [ 'access' => true, 'methods' => ['GET', 'POST'] ],
        ], $parameter);
    }

}
