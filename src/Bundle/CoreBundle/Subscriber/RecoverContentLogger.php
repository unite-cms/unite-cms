<?php

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Gedmo\Loggable\Entity\LogEntry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\DomainMember;

class RecoverContentLogger
{
    /**
     * @var TokenStorage $securityTokenStorage
     */
    private $securityTokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->securityTokenStorage = $tokenStorage;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $keyEntity => $entity) {
            if ($entity instanceof Content) {

                $changeSet = $uow->getEntityChangeSet($entity);

                // If the content was deleted and is no recovered, create log entry.
                if (array_key_exists(
                        'deleted',
                        $changeSet
                    ) && $changeSet['deleted'][0] !== null && $changeSet['deleted'][1] === null) {

                    // Create log entry for recovering.
                    $logEntries = $args->getEntityManager()->getRepository('GedmoLoggable:LogEntry')->getLogEntries(
                        $entity
                    );
                    $logEntry = new LogEntry();
                    $logEntry->setData(['data' => $entity->getData()]);
                    $logEntry->setAction('recover');
                    $logEntry->setObjectId($entity->getId());
                    $logEntry->setObjectClass(get_class($entity));
                    $logEntry->setUsername(
                        $this->securityTokenStorage->getToken() ? $this->securityTokenStorage->getToken()->getUser(
                        ) : 'Anonymous'
                    );
                    $logEntry->setLoggedAt();
                    $logEntry->setVersion($logEntries[0]->getVersion() + 1);
                    $args->getEntityManager()->persist($logEntry);
                    $classMetadata = $em->getClassMetadata(LogEntry::class);
                    $uow->computeChangeSet($classMetadata, $logEntry);
                }
            }
        }
    }

}
