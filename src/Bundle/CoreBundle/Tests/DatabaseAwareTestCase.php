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

    public function setUp()
    {
        parent::setUp();
        $this->em = static::$container->get('doctrine')->getManager();

        $this->purgeDatabase();

        #$schemaTool = new SchemaTool($this->em);
        #$metadata = $this->em->getMetadataFactory()->getAllMetadata();
        #$schemaTool->dropSchema($metadata);
        #$schemaTool->createSchema($metadata);
    }

    public function tearDown()
    {

        $this->purgeDatabase();
        #$schemaTool = new SchemaTool($this->em);
        #$metadata = $this->em->getMetadataFactory()->getAllMetadata();
        #$schemaTool->dropSchema($metadata);
        #$this->em->getConnection()->close();
        #parent::tearDown();
        #$this->em = null;
    }

    private function purgeDatabase()
    {
        $purger = new ORMPurger($this->em);
        $purger->purge();
    }
}
