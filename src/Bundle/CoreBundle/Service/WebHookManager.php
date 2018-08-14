<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:43
 */

namespace UniteCMS\CoreBundle\Service;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Security\WebhookExpressionChecker;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;

class WebHookManager
{
    /**
     * @var WebhookExpressionChecker webhookExpressionChecker
     */
    private $webhookExpressionChecker;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
    }

    public function processContent(Content $content, string $action) {

        if (!$content instanceof ContentType) return;

        $contentType = $content->getContentType();

        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($contentType->getIdentifier()) . 'Content', $contentType->getDomain());

        foreach ($contentType->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }

            $result = GraphQL::executeQuery(new Schema(['query' => $type]), $webhook->getQuery(), $content);
            $data = $this->container->get('jms_serializer')->serialize($result->data, 'json');
            $this->fire($webhook, $data);
        }

    }

    public function processSetting(Setting $setting, string $action) {

        if (!$setting instanceof SettingType) return;

        $settingType = $setting->getSettingType();

        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($settingType->getIdentifier()) . 'Setting', $settingType->getDomain());

        foreach ($settingType->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }

            $result = GraphQL::executeQuery(new Schema(['query' => $type]), $webhook->getQuery(), $setting);
            $data = $this->container->get('jms_serializer')->serialize($result->data, 'json');
            $this->fire($webhook, $data);
        }

    }

    private function fire(Webhook $webhook, string $jsonData) {

        $ssl_verify = ($webhook->getCheckSSL())? true:false;

        $client = new Client(['verify' => $ssl_verify]);

        $headers = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Secret-Key' => sha1($webhook->getSecretKey())
        ];

        try {

            $response = $client->post($webhook->getUrl(), $headers, $jsonData);

        } catch (\Exception $e) {
            
            $this->logger->error('Webhook error: '.$e->getMessage());
            return false;

        }
        
    }

}