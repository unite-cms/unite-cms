<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class CreateJWTTokenSubscriber implements EventSubscriberInterface
{
    protected $nextTTL = null;

    /**
     * @param int $ttl
     */
    public function setNextTTL(int $ttl) {
        $this->nextTTL = $ttl;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::JWT_CREATED => 'onJWTCreated'
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event) {
        $user = $event->getUser();
        if(!$user instanceof UserInterface) {
            return;
        }

        $data = $event->getData();

        // Allow other services to set next ttl here.
        if($this->nextTTL) {
            $data['exp'] = time() + $this->nextTTL;
            $this->nextTTL = null;
        }

        // Replace roles with our custom username / type information.
        unset($data['roles']);
        $data['username'] = $user->getUsername();
        $data['type'] = $user->getType();

        $event->setData($data);
    }
}
