<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.08.18
 * Time: 17:38
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

/**
 * @group slow
 */
class WebhookFunctionalTest extends DatabaseAwareTestCase
{
    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var string
     */
    private $domainConfiguration = '{
    "title": "Test controller webhook test",
    "identifier": "webhook_test", 
    "content_types": [
      {
        "title": "CT 1",
        "identifier": "ct1",
        "fields": [
            {
              "title": "Text",
              "identifier": "text",
              "type": "text",
              "settings": {}
            }
        ],
        "webhooks": [
            {
              "query": "query {type}",
              "url": "http://www.orf.at",
              "check_ssl": true,
              "secret_key": "",
              "action": "event == \"update\""
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
              "title": "Setting",
              "identifier": "text",
              "type": "text",
              "settings": {}
            }
        ],
        "webhooks": [
            {
              "query": "query {type}",
              "url": "http://www.orf.at",
              "check_ssl": true,
              "secret_key": "",
              "action": "event == \"update\""
            }
        ]
      }
    ]
  }';

    /**
     * @var User[]
     */
    private $users;

    private $userPassword = 'XXXXXXXXX';

    public function setUp()
    {
        parent::setUp();

        // Create Test Organization and import Test Domain.
        $this->organization = new Organization();
        $this->organization->setTitle('Test password reset')->setIdentifier('password_reset');

        $org2 = new Organization();
        $org2->setTitle('Org2')->setIdentifier('org2_org2');

        $this->domain = static::$container->get('unite.cms.domain_definition_parser')->parse($this->domainConfiguration);
        $this->domain->setOrganization($this->organization);

        $this->em->persist($this->organization);
        $this->em->persist($org2);
        $this->em->persist($this->domain);
        $this->em->flush();
        $this->em->refresh($this->organization);
        $this->em->refresh($org2);
        $this->em->refresh($this->domain);

        $this->users['domain_editor'] = new User();
        $this->users['domain_editor']
          ->setEmail('domain_editor@example.com')
          ->setName('Domain Editor')
          ->setRoles([User::ROLE_USER])
          ->setPassword(
            static::$container->get('security.password_encoder')->encodePassword(
              $this->users['domain_editor'],
              $this->userPassword
            )
          );

        $domainEditorOrgMember = new OrganizationMember();
        $domainEditorOrgMember->setRoles([Organization::ROLE_USER])->setOrganization($this->organization);
        $domainEditorDomainMember = new DomainMember();
        $domainEditorDomainMember->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->first());
        $this->users['domain_editor']->addOrganization($domainEditorOrgMember);
        $this->users['domain_editor']->addDomain($domainEditorDomainMember);

        foreach ($this->users as $key => $user) {
            $this->em->persist($this->users[$key]);
        }

        $this->em->flush();

        foreach ($this->users as $key => $user) {
            $this->em->refresh($this->users[$key]);
        }
    }

    /**
     * Test webhooks on Content and Setting Type
     */
    public function testWebhooks()
    {
        
    }
}