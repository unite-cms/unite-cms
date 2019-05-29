<?php

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Event\DomainConfigFileEvent;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SchemaCacheInvalidatorSubscriber implements EventSubscriberInterface
{
    /**
     * @var TagAwareCacheInterface $cache
     */
    protected $cache;

    public function __construct(TagAwareCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function flushDomainCache(DomainConfigFileEvent $event) {
        $this->cache->invalidateTags([join('.', [
            SchemaTypeManager::CACHE_PREFIX,
            $event->getDomain()->getOrganization()->getIdentifier(),
            $event->getDomain()->getIdentifier(),
        ])]);
    }

    public function flushUserCache($entity) {
        if($entity instanceof DomainMember && !empty($entity->getAccessor()) && !empty($entity->getAccessor()->getId())) {
            $this->cache->invalidateTags([join('.', [
                SchemaTypeManager::CACHE_PREFIX . '_user',
                $entity->getAccessor()->getId(),
            ])]);
        }

        if($entity instanceof DomainAccessor && !empty($entity->getId())) {
            $this->cache->invalidateTags([join('.', [
                SchemaTypeManager::CACHE_PREFIX . '_user',
                $entity->getId(),
            ])]);
        }
    }

    public function preRemove(LifecycleEventArgs $args) {
        $this->flushUserCache($args->getEntity());
    }

    public function postPersist(LifecycleEventArgs $args) {
        $this->flushUserCache($args->getEntity());
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $this->flushUserCache($args->getEntity());
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE => 'flushDomainCache',
            DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE => 'flushDomainCache',
        ];
    }
}
