<?php


namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SchemaCacheInvalidatorSubscriber
{
    /**
     * @var TagAwareCacheInterface $cache
     */
    protected $cache;

    public function __construct(TagAwareCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function preUpdate(PreUpdateEventArgs $args) {
        $entity = $args->getObject();

        if($entity instanceof Domain) {
            $this->cache->invalidateTags([SchemaTypeManager::CACHE_PREFIX . '.' . $entity->getOrganization()->getIdentifier() . '.' . $entity->getIdentifier()]);
        }

        if($entity instanceof DomainAccessor) {
            $this->cache->invalidateTags([SchemaTypeManager::CACHE_PREFIX . '_user.' . $entity->getId()]);
        }
    }

    public function preRemove(LifecycleEventArgs $args) {
        $entity = $args->getObject();

        if($entity instanceof Organization) {
            $this->cache->invalidateTags([SchemaTypeManager::CACHE_PREFIX . '.' . $entity->getIdentifier()]);
        }

        if($entity instanceof Domain) {
            $this->cache->invalidateTags([SchemaTypeManager::CACHE_PREFIX . '.' . $entity->getOrganization()->getIdentifier() . '.' . $entity->getIdentifier()]);
        }

        if($entity instanceof DomainAccessor) {
            $this->cache->invalidateTags([SchemaTypeManager::CACHE_PREFIX . '_user.' . $entity->getId()]);
        }
    }
}
