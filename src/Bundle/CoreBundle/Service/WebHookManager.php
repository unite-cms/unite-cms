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

class WebHookManager
{
    /**
     * @var WebhookExpressionChecker $webhookExpressionChecker
     */
    private $webhookExpressionChecker;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
        $this->logger = $logger;
    }

    public function process(array $webhooks, string $action) {

        #$type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($view->getContentType()->getIdentifier()) . 'Content', $view->getContentType()->getDomain());
        #$result = GraphQL::executeQuery(new Schema(['query' => $type]), $view->getContentType()->getPreview()->getQuery(), $content);
        #$data_uri = urlencode($this->container->get('jms_serializer')->serialize($result->data, 'json'));

        foreach ($webhooks as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getExpression(), $action)) {
                continue;
            }

            #dump($webhook);
            #$this->fire($webhook, $postData);
            
        }
        #exit;

    }

    private function fire(array $webhook, array $postData) {

        $client = new Client();

        $headers = [
            'Content-type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
        ];

        try {
            $response = $client->post($webhook['url'], $headers, json_encode($postData))->send();
        } catch (\Exception $e) {
            $this->logger->error('Webhook error: '.$e->getMessage());
            return false;
        }
        
    }

}