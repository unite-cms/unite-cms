<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SetCORSHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event) {

        // Only handle master requests.
        if(!$event->isMasterRequest()) {
            return;
        }

        // if this is a OPTIONS requests, we do not enter any controller but directly return a 200 response.
        if ('OPTIONS' == $event->getRequest()->getRealMethod()) {
            $event->setResponse(new Response());
        }
    }
    public function onKernelResponse(ResponseEvent $event) {

        // Only handle master requests.
        if (!$event->isMasterRequest()) {
            return;
        }

        // Modify the current response, adding CORS headers.
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
        $response->headers->set('Access-Control-Allow-Headers', 'authorization,content-type');
    }
}
