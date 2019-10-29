<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface;

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

        $data = $event->getData();
        unset($data['roles']);
        $data['username'] = $user->getUsername();
        $data['type'] = $user->getType();

        $event->setData($data);
    }
}
