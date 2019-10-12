<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use UniteCMS\CoreBundle\Domain\DomainManager;

class RequestDomainSubscriber implements EventSubscriberInterface
{
    const REQUEST_ATTRIBUTE = 'unite_domain';

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

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
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // If a param was found in the current request, use it to set the current domain.
        if($event->getRequest()->attributes->has(self::REQUEST_ATTRIBUTE)) {
            $this->domainManager->setCurrentDomainFromConfigId($event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE));
            return;
        }
    }
}
