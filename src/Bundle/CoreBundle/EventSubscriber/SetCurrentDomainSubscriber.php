<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use UniteCMS\CoreBundle\Domain\DomainManager;

class SetCurrentDomainSubscriber implements EventSubscriberInterface
{
    const REQUEST_ATTRIBUTE = 'unite_domain';
    const COMMAND_OPTION = 'domain';

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
            KernelEvents::REQUEST => ['onKernelRequest', 30],
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        // If the domain option was passed, use it to set the current domain.
        if($event->getInput()->hasOption(self::COMMAND_OPTION) && !empty($event->getInput()->getOption(self::COMMAND_OPTION))) {
            $this->domainManager->setCurrentDomainFromConfigId($event->getInput()->getOption(self::COMMAND_OPTION));
        }
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // If a param was found in the current request, use it to set the current domain.
        if($event->getRequest()->attributes->has(self::REQUEST_ATTRIBUTE)) {
            $this->domainManager->setCurrentDomainFromConfigId($event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE));
        }
    }
}
