<?php


namespace UniteCMS\DoctrineORMBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class DatabaseAwareTestCase extends SchemaAwareTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        parent::setUp();
        static::$container->get(DomainManager::class)
            ->clearDomain()
            ->setCurrentDomainFromConfigId('doctrine_orm');
        $this->em = static::$container->get('doctrine')->getManager('doctrine_orm');
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}
