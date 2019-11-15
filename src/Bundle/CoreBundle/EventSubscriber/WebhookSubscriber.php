<?php


namespace UniteCMS\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Log\LoggerInterface;

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

    public function __construct(DomainManager $domainManager, SaveExpressionLanguage $saveExpressionLanguage)
    {
        $this->domainManager = $domainManager;
        $this->expressionLanguage = $saveExpressionLanguage;
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
                $domain->log(LoggerInterface::WARNING, 'TODO: Webhook was not really executed at the moment...');
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
