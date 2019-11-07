<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;

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
     * @var LoggerInterface $domainLogger
     */
    protected $domainLogger;

    public function __construct(DomainManager $domainManager, SaveExpressionLanguage $saveExpressionLanguage, LoggerInterface $uniteCMSDomainLogger)
    {
        $this->domainManager = $domainManager;
        $this->expressionLanguage = $saveExpressionLanguage;
        $this->domainLogger = $uniteCMSDomainLogger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContentEventAfter::CREATE => 'onCreate',
            ContentEventAfter::UPDATE => 'onUpdate',
            ContentEventAfter::DELETE => 'onDelete',
        ];
    }

    protected function executeWebhook(ContentInterface $content, string $event) {

        $domain = $this->domainManager->current();
        $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());

        if(!$contentType) {
            return;
        }

        $values = [
            'event' => $event,
            'content' => $content,
        ];

        foreach($contentType->getWebhooks() as $webhook) {
            if((bool)$this->expressionLanguage->evaluate($webhook->getExpression(), $values)) {
                // TODO: Execute $webhook->url();
                $this->domainLogger->info('TODO: Webhook was not really executed at the moment...');
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
