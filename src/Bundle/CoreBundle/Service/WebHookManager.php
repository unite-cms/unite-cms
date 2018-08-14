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
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\Webhook;

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

        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($content->getContentType()->getIdentifier()) . 'Content', $content->getContentType()->getDomain());
        $result = GraphQL::executeQuery(new Schema(['query' => $type]), 'query { type }', $content);
        dump($result); exit;
        foreach ($contentType->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }
            $this->fire($webhook, $data);
        }

    }

    public function processSetting(Setting $setting, string $action) {
        
        foreach ($setting->getSettingType()->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }
            //$this->fire($webhook, $data);
        }

    }

    private function fire(Webhook $webhook, array $postData) {

        $ssl_verify = ($webhook->getCheckSSL())? true:false;

        $client = new Client(['verify' => $ssl_verify]);

        $headers = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Authorization' => $webhook->getSecretKey()
        ];

        /*try {

            $response = $client->post($webhook->getUrl(), $headers, json_encode($postData));
            print_r($response->getBody()->getContents()); exit;

        } catch (\Exception $e) {
            
            $this->logger->error('Webhook error: '.$e->getMessage());
            return false;

        }*/
        
    }

}