<?php


namespace UniteCMS\DoctrineORMBundle\EventSubscriber;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Event\ContentEventBefore;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Entity\Revision;

class RevisionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry $registry
     */
    protected $registry;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * RevisionListener constructor.
     *
     * @param ManagerRegistry $registry
     * @param Security $security
     * @param DomainManager $domainManager
     */
    public function __construct(ManagerRegistry $registry, Security $security, DomainManager $domainManager)
    {
        $this->registry = $registry;
        $this->security = $security;
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContentEventAfter::CREATE => 'onCreate',
            ContentEventAfter::UPDATE => 'onUpdate',
            ContentEventAfter::REVERT => 'onRevert',
            ContentEventAfter::DELETE => 'onDelete',
            ContentEventAfter::RECOVER => 'onRecover',
            ContentEventBefore::PERMANENT_DELETE => 'onPermanentDelete',
        ];
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return ObjectManager
     */
    protected function em(Domain $domain) : ObjectManager {
        return $this->registry->getManager($domain->getId());
    }

    /**
     * @param ContentInterface $content
     * @param string $persistType
     */
    protected function createRevision(ContentInterface $content, string $persistType) {
        $domain = $this->domainManager->current();

        // Only create revision for doctrine ORM content managers.
        if(!$domain->getContentManager() instanceof ContentManager) {
            return;
        }

        $revision = $this->em($domain)
            ->getRepository(Revision::class)
            ->createRevisionForContent($content, $persistType, $this->security->getUser());
        $this->em($domain)->persist($revision);
        $this->em($domain)->flush();
    }

    /**
     * @param ContentInterface $content
     */
    protected function deleteAllRevisions(ContentInterface $content) {
        $domain = $this->domainManager->current();

        // Only create revision for doctrine ORM content managers.
        if(!$domain->getContentManager() instanceof ContentManager) {
            return;
        }

        $this->em($domain)
            ->getRepository(Revision::class)
            ->deleteAllForContent($content->getId(), $content->getType());
        $this->em($domain)->flush();
    }

    /**
     * @param ContentEventAfter $event
     */
    public function onCreate(ContentEventAfter $event) {
        $this->createRevision($event->getContent(), ContentEvent::CREATE);
    }

    /**
     * @param ContentEventAfter $event
     */
    public function onUpdate(ContentEventAfter $event) {
        $this->createRevision($event->getContent(), ContentEvent::UPDATE);
    }

    /**
     * @param ContentEventAfter $event
     */
    public function onRevert(ContentEventAfter $event) {
        $this->createRevision($event->getContent(), ContentEvent::REVERT);
    }

    /**
     * @param ContentEventAfter $event
     */
    public function onDelete(ContentEventAfter $event) {
        $this->createRevision($event->getContent(), ContentEvent::DELETE);
    }

    /**
     * @param ContentEventAfter $event
     */
    public function onRecover(ContentEventAfter $event) {
        $this->createRevision($event->getContent(), ContentEvent::RECOVER);
    }

    /**
     * @param ContentEventBefore $event
     */
    public function onPermanentDelete(ContentEventBefore $event) {
        $this->deleteAllRevisions($event->getContent());
    }
}
