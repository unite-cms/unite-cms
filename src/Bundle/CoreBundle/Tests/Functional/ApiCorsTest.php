<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 20.07.18
 * Time: 10:33
 */

namespace App\Bundle\CoreBundle\Tests\Functional;


use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ApiCorsTest extends DatabaseAwareTestCase
{
    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var ApiKey $apiKey
     */
    private $apiKey;

    /**
     * @var string $apiUrl
     */
    private $apiUrl;

    public function setUp()
    {
        parent::setUp();
        $this->client = static::$container->get('test.client');
        $this->client->followRedirects(false);

        $org = new Organization();
        $org
            ->setTitle('ORG')
            ->setIdentifier('org');

        $this->domain = new Domain();
        $this->domain
            ->setOrganization($org)
            ->setIdentifier('domain')
            ->setTitle('Domain');

        $this->apiKey = new ApiKey();
        $this->apiKey
            ->setToken('XXX')
            ->setName('API KEY')
            ->setOrigin('https://example.com');

        $org->addApiKey($this->apiKey);

        $domainMember = new DomainMember();
        $domainMember
            ->setDomainMemberType($this->domain->getDomainMemberTypes()->first())
            ->setAccessor($this->apiKey);

        $this->domain->addMember($domainMember);

        $this->em->persist($org);
        $this->em->persist($this->domain);
        $this->em->persist($this->apiKey);
        $this->em->persist($domainMember);
        $this->em->flush();

        $this->apiUrl = static::$container->get('router')->generate('unitecms_core_api', [
            'organization' => $org->getIdentifier(),
            'domain' => $this->domain->getIdentifier(),
        ], Router::ABSOLUTE_URL);
    }

    public function testApiCORSHandling() {

        // Do an OPTIONS request to the API endpoint without user credentials
        $this->client->request('OPTIONS', $this->apiUrl);

        // Make sure, that the request was successful.
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Make sure, that all required response header are set.
        $this->assertEquals('*', $this->client->getResponse()->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST', $this->client->getResponse()->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('authorization,content-type', $this->client->getResponse()->headers->get('Access-Control-Allow-Headers'));

        // Now, do a real POST request by using the api key. Now the access-control-allow-origin value should be API KEY specific.
        $this->client->request('POST', $this->apiUrl, [], [], [
            'HTTP_AUTHORIZATION' => $this->apiKey->getToken(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['query' => '{ find { total } }']));

        // The response should include all required response headers and should be successful.
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('https://example.com', $this->client->getResponse()->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST', $this->client->getResponse()->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('authorization,content-type', $this->client->getResponse()->headers->get('Access-Control-Allow-Headers'));
    }
}