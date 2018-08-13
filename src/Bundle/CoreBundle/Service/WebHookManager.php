<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:43
 */

namespace UniteCMS\CoreBundle\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\Security\WebhookExpressionChecker;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class WebHookManager
{
    /**
     * @var WebhookExpressionChecker $webhookExpressionChecker
     */
    private $webhookExpressionChecker;

    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    public function __construct(SchemaTypeManager $schemaTypeManager, LoggerInterface $logger)
    {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
        $this->logger = $logger;
    }

    public function processContentType(ContentType $contentType, string $action, array $data) {
        #$type = $this->schemaTypeManager->getSchemaType(ucfirst($contentType->getIdentifier()) . 'Content', $contentType->getDomain());
        #$content = new Content();
        #$content->setData($data);
        #$result = GraphQL::executeQuery(new Schema(['query' => $type]), 'query { type }', $content);
        #dump($result); exit;
        #$data_uri = urlencode($this->container->get('jms_serializer')->serialize($result->data, 'json'));
        foreach ($contentType->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }
            $this->fire($webhook, $data);
        }

    }

    public function processSettingType(SettingType $settingType, string $action, array $data) {
        
        foreach ($contentType->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getAction(), $action)) {
                continue;
            }
            $this->fire($webhook, $data);
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

        try {

            $response = $client->post($webhook->getUrl(), $headers, json_encode($postData));
            #print_r($response->getBody()->getContents()); exit;

        } catch (\Exception $e) {
            
            $this->logger->error('Webhook error: '.$e->getMessage());
            return false;

        }
        
    }

}