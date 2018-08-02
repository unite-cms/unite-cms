<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:27
 */

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Service\WebhookManager;

class WebHookSubscriber
{
    private $webHookManager;

    public function __construct(WebhookManager $webHookManager)
    {
        $this->webHookManager = $webHookManager;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->fireHook($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->fireHook($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->fireHook($args);
    }

    public function fireHook(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Content) {
            #$entityManager = $args->getEntityManager();
            $this->webHookManager->fire();
        }
    }

}