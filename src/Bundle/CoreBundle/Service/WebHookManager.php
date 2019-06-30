<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 15:43
 */

namespace UniteCMS\CoreBundle\Service;

use GraphQL\GraphQL;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class WebHookManager
{
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
        /**
         * @var ContentType|SettingType $entity
         */
        $entity = $content->getEntity();

        if (empty($entity->getWebHooks())) {
           return;
        }

        $schema = $this->container->get('unite.cms.graphql.schema_type_manager')->createSchema($entity->getDomain(), ucfirst($entity->getIdentifier()) . $type);

        $expressionChecker = new UniteExpressionChecker();
        $expressionChecker
            ->clearVariables()
            ->registerFieldableContent($content)
            ->registerVariable('event', $event_name);

        if($content instanceof Content) {
            $expressionChecker->registerDoctrineContentFunctionsProvider($this->container->get('doctrine.orm.entity_manager'), $content->getContentType());
        }

        foreach ($entity->getWebHooks() as $webhook) {

            // in case of SettingType always fire update event
            if (!$expressionChecker->evaluateToBool($webhook->getCondition())) {
                continue;
            }

            $result = GraphQL::executeQuery($schema, $webhook->getQuery(), $content);

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
     * @param Webhook $webhook
     * @param array $data
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
            if ($webhook->getContentType() == 'json')
            {

                $post_data = [
                    'verify' => $ssl_verify,
                    'json' => $data,
                    'headers' => [
                        'Content-type' => 'application/json; charset=utf-8'
                    ]
                ];

            }
            else {

                $post_data = [
                    'verify' => $ssl_verify,
                    'form_params' => $data,
                    'headers' => [
                        'Content-type' => 'application/x-www-form-urlencoded; charset=utf-8'
                    ]
                ];

            }

            if ($webhook->getAuthenticationHeader()) {
                $post_data['headers']['Authorization'] = $webhook->getAuthenticationHeader();
            }

            $this->client->request('POST', $webhook->getUrl(), $post_data);
            return true;

        } catch (GuzzleException $exception)
        {
            // @todo Log errors to a Log entity which will be presented to the user
            $this->logger->error('A network error occurred. Webhook was not sent.', array('exception' => $exception));
            return false;

        }
        
    }

}