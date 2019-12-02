<?php

namespace UniteCMS\DoctrineORMBundle\Logger;

use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Log\LogInterface;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\DoctrineORMBundle\Entity\Log;

class Logger implements LoggerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * ContentManager constructor.
     *
     * @param ManagerRegistry $registry
     * @param Security $security
     */
    public function __construct(ManagerRegistry $registry, Security $security) {
        $this->registry = $registry;
        $this->security = $security;
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
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return \UniteCMS\DoctrineORMBundle\Repository\ContentRepository
     */
    protected function repository(Domain $domain) : ObjectRepository {
        return $this->em($domain)->getRepository(Log::class);
    }

    /**
     * {@inheritDoc}
     */
    public function log(Domain $domain, string $level, string $message, string $username = null): LogInterface {
        $log = new Log($level, $message);

        if($username) {
            $log->setUsername($username);
        }

        else if ($user = $this->security->getUser()) {
            $log->setUsername($user->getUsername());
        }

        $this->em($domain)->persist($log);
        $this->em($domain)->flush();
        return $log;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogs(Domain $domain, DateTime $before, DateTime $after = null, int $limit = 100, int $offset = 0): array {
        $builder = $this->repository($domain)->createQueryBuilder('l')
            ->select('l')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('l.created', 'DESC')
            ->where('l.created < :before')->setParameter('before', $before);

        if($after) {
            $builder->andWhere('l.created > :after')->setParameter('after', $after);
        }

        return $builder->getQuery()->execute();
    }
}
