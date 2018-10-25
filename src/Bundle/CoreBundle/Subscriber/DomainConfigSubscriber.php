<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 18.10.18
 * Time: 19:13
 */

namespace UniteCMS\CoreBundle\Subscriber;


use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

/**
 * Syncs the domain entity config with the filesystem.
 * Class DomainConfigSubscriber
 * @package UniteCMS\CoreBundle\Subscriber
 */
class DomainConfigSubscriber
{
    /**
     * @var DomainConfigManager $domainConfigManager
     */
    private $domainConfigManager;

    public function __construct(DomainConfigManager $domainConfigManager)
    {
        $this->domainConfigManager = $domainConfigManager;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->domainConfigManager->updateConfig($entity, false);
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->domainConfigManager->updateConfig($entity, true);
        }
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->domainConfigManager->removeConfig($entity);
        }
    }
}