<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use DateTime;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;
use UniteCMS\DoctrineORMBundle\Entity\Content;
use UniteCMS\DoctrineORMBundle\Entity\Revision;

class ContentManager implements ContentManagerInterface
{
    const ENTITY = Content::class;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var ContentInterface[]
     */
    protected $contentToPersist = [];

    /**
     * @var ContentInterface[]
     */
    protected $contentToRemove = [];

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
        return $this->em($domain)->getRepository(static::ENTITY);
    }

    /**
     * {@inheritDoc}
     */
    public function transactional(Domain $domain, callable $transaction) {
        $ret = null;
        $retries = 4;

        while($retries > 0) {
            try {
                $this->em($domain)->beginTransaction();
                $ret = $transaction();
                $this->em($domain)->getConnection()->commit();

                return $ret;
            } catch (DeadlockException $deadlockException) {
                $retries--;

                if($retries <= 0) {
                    throw $deadlockException;
                }

                // Wait for 1 sec and try again.
                sleep(1);

            } catch (\Exception $e) {
                $this->em($domain)->getConnection()->rollBack();
                throw $e;
            }
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function find(Domain $domain, string $type, ContentCriteria $criteria, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {
        return new ContentResult(
            $this->repository($domain),
            $type,
            $criteria,
            $includeDeleted,
            $resultFilter
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false): ?ContentInterface {
        return $this->repository($domain)->typedFind($type, $id, $includeDeleted);
    }

    /**
     * {@inheritDoc}
     */
    public function create(Domain $domain, string $type): ContentInterface {
        $class = static::ENTITY;
        $content = new $class($type);
        $this->contentToPersist[] = $content;
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Domain $domain, ContentInterface $content, array $inputData = []): ContentInterface {
        return $content
            ->setData($inputData)
            ->setUpdated(new DateTime('now'));
    }

    /**
     * {@inheritDoc}
     */
    public function revert(Domain $domain, ContentInterface $content, int $version) : ContentInterface {

        $revision = $this->em($domain)
            ->getRepository(Revision::class)
            ->findOneForContent($content, $version);

        if(!$revision) {
            throw new InvalidContentVersionException();
        }

        $content->setData($revision->getData());
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function revisions(Domain $domain, ContentInterface $content, int $limit = 20, int $offset = 0, array $orderBy = ['version' => 'DESC']) : array {
        return $this->em($domain)
            ->getRepository(Revision::class)
            ->findForContent($content, $limit, $offset, $orderBy);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Domain $domain, ContentInterface $content): ContentInterface {
        $content->setDeleted(new DateTime());
        return $content;
    }

    public function recover(Domain $domain, ContentInterface $content) : ContentInterface {
        return $content->setDeleted(null);
    }

    /**
     * {@inheritDoc}
     */
    public function permanentDelete(Domain $domain, ContentInterface $content): ContentInterface {
        $this->contentToRemove[] = $content;
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function flush(Domain $domain) : void {

        foreach($this->contentToPersist as $content) {
            $this->em($domain)->persist($content);
        }

        foreach($this->contentToRemove as $content) {
            $this->em($domain)->remove($content);
        }

        $this->em($domain)->flush();
        $this->contentToPersist = [];
        $this->contentToRemove = [];
    }

    /**
     * {@inheritDoc}
     */
    public function noFlush(Domain $domain) : void {

        foreach($this->contentToPersist as $content) {
            unset($content);
        }

        $this->contentToPersist = [];
        $this->contentToRemove = [];
    }
}
