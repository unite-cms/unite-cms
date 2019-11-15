<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Log\LoggerInterface;

class ContentLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContentEventAfter::CREATE => 'onCreate',
            ContentEventAfter::UPDATE => 'onUpdate',
            ContentEventAfter::REVERT => 'onRevert',
            ContentEventAfter::DELETE => 'onDelete',
            ContentEventAfter::RECOVER => 'onRecover',
            ContentEventAfter::PERMANENT_DELETE => 'onPermanentDelete',
        ];
    }

    /**
     * @param ContentEvent $event
     * @param string $name
     */
    protected function log(ContentEvent $event, string $name) {
        $message = sprintf('%s "%s" content with id "%s".', $name, $event->getContent()->getType(), $event->getContent()->getId());
        $this->domainManager->current()->log(LoggerInterface::NOTICE, $message);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onCreate(ContentEvent $event) {
        $this->log($event, ContentEvent::CREATE);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onUpdate(ContentEvent $event) {
        $this->log($event, ContentEvent::UPDATE);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onDelete(ContentEvent $event) {
        $this->log($event, ContentEvent::DELETE);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onRevert(ContentEvent $event) {
        $this->log($event, ContentEvent::REVERT);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onRecover(ContentEvent $event) {
        $this->log($event, ContentEvent::RECOVER);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onPermanentDelete(ContentEvent $event) {
        $this->log($event, ContentEvent::PERMANENT_DELETE);
    }
}
