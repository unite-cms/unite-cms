<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;

class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    public function __construct(DomainManager $domainManager, LoggerInterface $logger)
    {
        $this->domainManager = $domainManager;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContentEvent::CREATE => 'onCreate',
            ContentEvent::UPDATE => 'onUpdate',
            ContentEvent::DELETE => 'onDelete',
        ];
    }

    protected function executeWebhook(ContentInterface $content, string $event) {

        $domain = $this->domainManager->current();
        $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());

        if(!$contentType) {
            return;
        }

        foreach($contentType->getWebhooks() as $webhook) {
            if((bool)$this->expressionLanguage->evaluate($webhook->getExpression())) {
                // TODO: Execute $webhook->url();
                $this->logger->info('Webhook...');
            }
        }
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onCreate(ContentEvent $event)
    {
        $this->executeWebhook($event->getContent(), ContentEvent::CREATE);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onUpdate(ContentEvent $event)
    {
        $this->executeWebhook($event->getContent(), ContentEvent::UPDATE);
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onDelete(ContentEvent $event)
    {
        $this->executeWebhook($event->getContent(), ContentEvent::DELETE);
    }
}
