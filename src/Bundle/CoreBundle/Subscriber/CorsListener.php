<?php

namespace UniteCMS\CoreBundle\Subscriber;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Entity\ApiKey;

class CorsListener
{
    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    /**
     * @var FirewallMap $firewallMap
     */
    private $firewallMap;

    public function __construct(FirewallMap $firewallMap, TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->firewallMap = $firewallMap;
    }

    public function onKernelRequest(RequestEvent $event) {

        // Only handle master requests.
        if(!$event->isMasterRequest()) {
            return;
        }

        // Only handle requests for api firewall.
        if($this->firewallMap->getFirewallConfig($event->getRequest())->getName() != 'api') {
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

        // Only handle requests for api firewall.
        if($this->firewallMap->getFirewallConfig($event->getRequest())->getName() != 'api') {
            return;
        }

        // Modify the current response, adding CORS headers.
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
        $response->headers->set('Access-Control-Allow-Headers', 'authorization,content-type');

        // If this is a authenticated request (Not OPTIONS) we can use the current api key entity to get allowed origins.
        $token = $this->tokenStorage->getToken();
        if($token instanceof PostAuthenticationGuardToken) {
            $user = $token->getUser();
            if($user instanceof ApiKey) {
                $response->headers->set('Access-Control-Allow-Origin', empty($user->getOrigin()) ? '*' : $user->getOrigin());
            }
        }
    }
}
