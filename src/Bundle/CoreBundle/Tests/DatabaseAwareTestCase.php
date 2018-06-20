<?php

namespace UniteCMS\CoreBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

abstract class DatabaseAwareTestCase extends ContainerAwareTestCase
{

    /**
     * @var EntityManager
     */
    protected $em;

    protected $databaseStrategy = "STRATEGY_PURGE";

    public function setUp()
    {
        parent::setUp();

        $this->em = static::$container->get('doctrine')->getManager();

        if ($this->databaseStrategy == "STRATEGY_RECREATE") {
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
        else {
            $this->purgeDatabase();
        }
    }

    public function tearDown()
    {
        if ($this->databaseStrategy == "STRATEGY_RECREATE") {
            $this->purgeDatabase();
            $this->em->getConnection()->close();
        }
        else {
            $this->purgeDatabase();
        }
        $this->em->getConnection()->close();
        parent::tearDown();
        $this->em = null;
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->em);
        $purger->purge();
    }

}
