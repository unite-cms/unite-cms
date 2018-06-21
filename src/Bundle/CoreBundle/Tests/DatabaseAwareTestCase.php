<?php

namespace UniteCMS\CoreBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

abstract class DatabaseAwareTestCase extends ContainerAwareTestCase
{

    /**
     * Strategies how to prepare the database before tests are run. *Recreate* will drop the whole db schema and
     * recreate it. *Purge* will just purge all the data.
     */
    const STRATEGY_PURGE = 'purge';
    const STRATEGY_RECREATE = 'recreate';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Choose a database strategy for this test case. Note: This will affect test performance dramatically.
     * @var string
     */
    protected $databaseStrategy = DatabaseAwareTestCase::STRATEGY_PURGE;

    public function setUp()
    {
        parent::setUp();

        $this->em = static::$container->get('doctrine')->getManager();

        if ($this->databaseStrategy === DatabaseAwareTestCase::STRATEGY_RECREATE) {
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        if($this->databaseStrategy === DatabaseAwareTestCase::STRATEGY_PURGE) {
            $this->purgeDatabase();
        }
    }

    public function tearDown()
    {
        if ($this->databaseStrategy === DatabaseAwareTestCase::STRATEGY_RECREATE) {
            $this->purgeDatabase();
            $this->em->getConnection()->close();
        }

        if($this->databaseStrategy === DatabaseAwareTestCase::STRATEGY_PURGE) {
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
