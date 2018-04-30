<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 01.12.17
 * Time: 10:28
 */

namespace UniteCMS\CoreBundle\Tests\Service;

use Symfony\Component\HttpFoundation\Request;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class UniteCMSManagerTest extends DatabaseAwareTestCase
{

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test unite CMS Manager load organization and dmoain",
    "identifier": "unite_cms_manager", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "text" }
        ], 
        "views": [
            { "title": "All", "identifier": "all", "type": "table" },
            { "title": "Other", "identifier": "other", "type": "table" }
        ]
      }
    ], 
    "setting_types": [
      {
        "title": "ST 1",
        "identifier": "st1", 
        "fields": [
            { "title": "Field 1", "identifier": "f1", "type": "text" }, 
            { "title": "Field 2", "identifier": "f2", "type": "text" }
        ]
      }
    ]
  }';

    public function setUp()
    {
        parent::setUp();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Organization')->setIdentifier('org1');
        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($this->domain);
    }

    public function testGettingOrgAndDomainWithoutRequest() {
        // cms manager should silently return null if it could not find an organization or domain
        $this->assertNull($this->container->get('unite.cms.manager')->getOrganization());
        $this->assertNull($this->container->get('unite.cms.manager')->getDomain());

        $this->container->get('request_stack')->push(new Request(
            [], [], [
                'organization' => 'foo',
                'domain' => 'baa',
            ]
        ));
    }

    public function testGettingOrgAndDomainWithInvalidOrganizationIdentifier() {
        $this->container->get('request_stack')->push(new Request(
            [], [], [
                'organization' => 'foo',
                'domain' => 'baa',
            ]
        ));
        $this->assertNull($this->container->get('unite.cms.manager')->getOrganization());
        $this->assertNull($this->container->get('unite.cms.manager')->getDomain());
    }

    public function testGettingOrgAndDomainWithInvalidDomainIdentifier() {
        $this->container->get('request_stack')->push(new Request(
            [], [], [
                'organization' => $this->organization,
                'domain' => 'baa',
            ]
        ));
        $this->assertEquals($this->organization->getIdentifier(), $this->container->get('unite.cms.manager')->getOrganization()->getIdentifier());
        $this->assertNull($this->container->get('unite.cms.manager')->getDomain());
    }

    public function testGettingOriginalDomainFromManager() {

        $title = $this->domain->getTitle();
        $cTitle = $this->domain->getContentTypes()->first()->getTitle();
        $sTitle = $this->domain->getSettingTypes()->first()->getTitle();

        $this->container->get('request_stack')->push(new Request(
            [], [], [
                'organization' => $this->organization,
                'domain' => $this->domain,
            ]
        ));
        $originalDomain = $this->container->get('unite.cms.manager')->getDomain();

        // Change domain and content type title on loaded domain
        $this->domain->setTitle('New Title');
        $this->domain->getContentTypes()->first()->setTitle('New Title');
        $this->domain->getSettingTypes()->first()->setTitle('New Title');

        $this->assertNotEquals($title, $this->domain->getTitle());
        $this->assertNotEquals($cTitle, $this->domain->getContentTypes()->first()->getTitle());
        $this->assertNotEquals($sTitle, $this->domain->getSettingTypes()->first()->getTitle());

        // Make sure, that unite.cms.manager's original domain was not altered but ids should be present
        $this->assertEquals($title, $originalDomain->getTitle());
        $this->assertEquals($cTitle, $originalDomain->getContentTypes()->first()->getTitle());
        $this->assertEquals($sTitle, $originalDomain->getSettingTypes()->first()->getTitle());

        $this->assertEquals($this->organization->getId(), $originalDomain->getOrganization()->getId());
        $this->assertEquals($this->domain->getContentTypes()->first()->getId(), $originalDomain->getContentTypes()->first()->getId());
        $this->assertEquals($this->domain->getSettingTypes()->first()->getId(), $originalDomain->getSettingTypes()->first()->getId());
    }

    public function testTryingToUpdateShouldNotWork() {

        $oTitle = $this->organization->getTitle();
        $dTitle = $this->domain->getTitle();
        $cTitle = $this->domain->getContentTypes()->first()->getTitle();
        $sTitle = $this->domain->getSettingTypes()->first()->getTitle();

        $user = new User();
        $user->setFirstname('XXX')->setLastname('XXX')->setPassword('XXX')->setEmail('xxx@xxx.com');
        $orgMember = new OrganizationMember();
        $orgMember->setAuthenticated($user);
        $this->organization->addMember($orgMember);
        $this->em->persist($user);
        $this->em->flush();

        $this->container->get('request_stack')->push(new Request(
            [], [], [
                'organization' => $this->organization,
                'domain' => $this->domain,
            ]
        ));
        $originalOrganization = $this->container->get('unite.cms.manager')->getOrganization();
        $originalDomain = $this->container->get('unite.cms.manager')->getDomain();

        // unite.cms.manager will return cloned, un-managed objects, so updating or persisting them should not work.
        $originalOrganization->setTitle('new Title');
        $this->assertFalse($this->em->contains($originalOrganization));
        $this->assertFalse($this->em->contains($originalOrganization->getDomains()->first()));
        $this->em->flush();

        $this->em->refresh($this->organization);
        $this->assertEquals($oTitle, $this->organization->getTitle());


        $originalDomain->setTitle('new Title');
        $this->assertFalse($this->em->contains($originalDomain));
        $this->em->flush();

        $this->em->refresh($this->domain);
        $this->assertEquals($dTitle, $this->domain->getTitle());


        $originalDomain->getContentTypes()->first()->setTitle('new Title');
        $this->assertFalse($this->em->contains($originalDomain->getContentTypes()->first()));
        $this->em->flush();

        $this->em->refresh($this->domain->getContentTypes()->first());
        $this->assertEquals($cTitle, $this->domain->getContentTypes()->first()->getTitle());



        $originalDomain->getSettingTypes()->first()->setTitle('new Title');
        $this->assertFalse($this->em->contains($originalDomain->getSettingTypes()->first()));
        $this->em->flush();

        $this->em->refresh($this->domain->getSettingTypes()->first());
        $this->assertEquals($sTitle, $this->domain->getSettingTypes()->first()->getTitle());
    }
}
