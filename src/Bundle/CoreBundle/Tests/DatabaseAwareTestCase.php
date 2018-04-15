<?php

namespace UniteCMS\CoreBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

abstract class DatabaseAwareTestCase extends ContainerAwareTestCase
{

    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        parent::setUp();
        $this->em = $this->container->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function tearDown()
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $this->em->getConnection()->close();
        parent::tearDown();
        $this->em = null;
    }
}
