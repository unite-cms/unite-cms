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
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Service\WebhookManager;

class WebHookSubscriber
{
    /**
     * @var WebhookManager $webHookManager
     */
    private $webHookManager;

    public function __construct(WebhookManager $webHookManager)
    {
        $this->webHookManager = $webHookManager;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Content) {
            $this->fireHook($args, 'delete');
        }

        if ($entity instanceof Setting) {
            $this->fireHook($args, 'update');
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Content) {
            $this->fireHook($args, 'create');
        }

        if ($entity instanceof Setting) {
            $this->fireHook($args, 'update');
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->fireHook($args, 'update');
    }

    public function fireHook(LifecycleEventArgs $args, string $event)
    {
        $entity = $args->getObject();

        if ($entity instanceof Content)
        {
            $this->webHookManager->process($args->getObject(), $event, 'Content');
        }

        if ($entity instanceof Setting)
        {
            $this->webHookManager->process($args->getObject(), $event, 'Setting');
        }

    }
}