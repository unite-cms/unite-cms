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
use GuzzleHttp\Exception\TransferException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use UniteCMS\CoreBundle\Security\WebhookExpressionChecker;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\Entity\FieldableContent;

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

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
        $this->client = new Client();
    }

    /**
     * Processes the given webhooks of ContentType
     *
     * @param FieldableContent $content
     * @param string $action
     * @param string $type
     *
     * @return void
     */
    public function process(FieldableContent $content, string $action, string $type) : void
    {
        $entity = $content->getEntity();

        if (empty($entity->getWebHooks())) {
           return;
        }

        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($entity->getIdentifier()) . $type, $entity->getDomain());

        foreach ($entity->getWebHooks() as $webhook) {
            if (!$this->webhookExpressionChecker->evaluate($webhook->getCondition(), $action, $content)) {
                continue;
            }

            $result = GraphQL::executeQuery(new Schema(['query' => $type]), $webhook->getQuery(), $content);
            $this->fire($webhook, $result->data);
        }

    }

    /**
     * Executes the given webhooks
     *
     * @param Webhook[]
     * @param array $data
     *
     * @throws TransferException if the request fails
     *
     * @return bool
     */
    private function fire(Webhook $webhook, array $data) : bool
    {
        $ssl_verify = ($webhook->getCheckSSL())? true:false;

        try
        {
            $post_data = [
                'verify' => $ssl_verify,
                'json' => $data,
                'headers' => [
                     'Content-type' => 'application/json; charset=utf-8'
                 ]
            ];

            if ($webhook->getAuthenticationHeader()) {
                $post_data['headers']['Authorization'] = $webhook->getAuthenticationHeader();
            }

            $response = $this->client->request('POST', $webhook->getUrl(), $post_data);

            return true;

        } catch (TransferException $exception)
        {

            $this->logger->error('A network error occurred. Webhook was not sent.', array('exception' => $exception));
            return false;

        }
        
    }

}