<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\User\UserInterface;

class CreateJWTTokenSubscriber implements EventSubscriberInterface
{

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

        $event->setData([
            'username' => $user->getUsername(),
            'type' => $user->getType()
        ]);
    }
}
