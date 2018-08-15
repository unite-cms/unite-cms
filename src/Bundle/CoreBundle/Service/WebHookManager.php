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

    /**
     * @var Client $client
     */
    private $client;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, Client $client)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
        $this->client = $client;
    }

    /**
     * Processes the given webhooks of ContentType
     *
     * @param Setting $setting
     * @param string $action
     *
     * @return void
     */
    public function processContent(Content $content, string $action) : void
    {
        $contentType = $content->getContentType();

        if (empty($contentType->getWebHooks())) {
           return;
        }

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

    /**
     * Processes the given webhooks of SettingType
     *
     * @param Setting $setting
     * @param string $action
     *
     * @return void
     */
    public function processSetting(Setting $setting, string $action) : void
    {

        $settingType = $setting->getSettingType();

        if (empty($settingType->getWebHooks())) {
            return;
        }

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

    /**
     * Executes the given webhooks
     *
     * @param Webhook[]
     * @param string $jsonData
     *
     * @return bool
     */
    private function fire(Webhook $webhook, string $jsonData) : bool
    {

        $ssl_verify = ($webhook->getCheckSSL())? true:false;

        $headers = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Secret-Key' => sha1($webhook->getSecretKey())
        ];

        try
        {
            $response = $this->client->post($webhook->getUrl(), $headers, $jsonData);
            #print_r($response->getBody()->getContents());
            return true;

        } catch (\Exception $e)
        {
            
            $this->logger->error('Webhook error: '.$e->getMessage());
            return false;

        }
        
    }

}