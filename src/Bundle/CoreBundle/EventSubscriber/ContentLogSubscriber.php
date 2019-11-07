<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;

class ContentLogSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface $domainLogger
     */
    protected $domainLogger;

    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(LoggerInterface $uniteCMSDomainLogger, Security $security)
    {
        $this->domainLogger = $uniteCMSDomainLogger;
        $this->security = $security;
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

        if($user = $this->security->getUser()) {
            $message .= sprintf(' by user "%s" ', $user->getUsername());
        }

        $this->domainLogger->info($message, ['content' => $event->getContent()]);
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
