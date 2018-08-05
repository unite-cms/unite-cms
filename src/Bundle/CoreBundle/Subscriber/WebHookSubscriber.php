<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:27
 */

namespace UniteCMS\CoreBundle\Subscriber;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
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
        $this->fireHook($args, 'delete');
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->fireHook($args, 'create');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->fireHook($args, 'update');
    }

    public function fireHook(LifecycleEventArgs $args, string $event)
    {
        $entity = $args->getEntity();
        $data = [];

        if ($entity instanceof Content) {
            #$type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($view->getContentType()->getIdentifier()) . 'Content', $view->getContentType()->getDomain());
            #$result = GraphQL::executeQuery(new Schema(['query' => $type]), $view->getContentType()->getPreview()->getQuery(), $content);
            #$data_uri = urlencode($this->container->get('jms_serializer')->serialize($result->data, 'json'));
            $content_type = $entity->getContentType();
            $data = $entity->getData();
            $this->webHookManager->process($content_type->getWebhooks(), $data, $event);
        }

        if ($entity instanceof Setting) {
            $setting_type = $entity->getSettingType();
            $data = $entity->getData();
            $this->webHookManager->process($setting_type->getWebhooks(), $data, $event);
        }
    }
}