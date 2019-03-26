<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 18.10.18
 * Time: 19:13
 */

namespace UniteCMS\CoreBundle\Subscriber;


use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\InvalidOrganizationConfigurationException;
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
            $this->domainConfigManager->updateConfig($entity, null,false);
        }

        if($entity instanceof Organization) {
            $this->domainConfigManager->createOrganizationFolder($entity);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->domainConfigManager->updateConfig($entity, $args->hasChangedField('identifier') ? $args->getOldValue('identifier') : null, true);
        }

        if($entity instanceof Organization) {
            if($args->hasChangedField('identifier')) {
                $this->domainConfigManager->renameOrganizationFolder($entity, $args->getOldValue('identifier'));
            }
        }
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->domainConfigManager->removeConfig($entity);
        }

        if($entity instanceof Organization) {
            $this->domainConfigManager->removeOrganizationFolder($entity);
        }
    }
}
