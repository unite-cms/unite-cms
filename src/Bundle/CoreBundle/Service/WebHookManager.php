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
     * @param string $event_name
     * @param string $type
     *
     * @todo Log errors to a Log entity which will be presented to the user
     *
     * @return void
     */
    public function process(FieldableContent $content, string $event_name, string $type) : void
    {
        $entity = $content->getEntity();

        if (empty($entity->getWebHooks())) {
           return;
        }

        #dump( $this->container->get('unite.cms.graphql.schema_type_manager'));


        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(ucfirst($entity->getIdentifier()) . $type, $entity->getDomain());

        #dump($type); exit;



        foreach ($entity->getWebHooks() as $webhook) {

            // in case of SettingType always fire update event
            if (!$this->webhookExpressionChecker->evaluate($webhook->getCondition(), $event_name, $content)) {
                continue;
            }

            $query = 'query { variants { type } }';
            $result = GraphQL::executeQuery(new Schema(['query' => $type]), $query, $content);
            #$result = GraphQL::executeQuery(new Schema(['query' => $type]), $webhook->getQuery(), $content);

            dump($result); exit;
            
            if (empty($result->errors)) {
                $this->fire($webhook, $result->data);
            }
            else {
                // @todo Log errors to a Log entity which will be presented to the user
                foreach ($result->errors as $error) {
                    $this->logger->error('Error Resolving Webhook query.', array('exception' => $error));
                }
            }
            
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
     * @todo Log errors to a Log entity which will be presented to the user
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

            // @todo Log errors to a Log entity which will be presented to the user
            $this->logger->error('A network error occurred. Webhook was not sent.', array('exception' => $exception));
            return false;

        }
        
    }

}