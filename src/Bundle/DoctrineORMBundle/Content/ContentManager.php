<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\DoctrineORMBundle\Entity\Content;
use UniteCMS\DoctrineORMBundle\Repository\ContentRepository;

class ContentManager implements ContentManagerInterface
{
    /**
     * @var \Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected $registry;

    /**
     * ContentManager constructor.
     *
     * @param \Symfony\Bridge\Doctrine\RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return EntityManager
     */
    protected function em(Domain $domain) : EntityManager {
        return $this->registry->getEntityManager($domain->getId());
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return \UniteCMS\DoctrineORMBundle\Repository\ContentRepository
     */
    protected function repository(Domain $domain) : ContentRepository {
        return $this->em($domain)->getRepository(Content::class);
    }

    /**
     * {@inheritDoc}
     */
    public function find(Domain $domain, string $type, ContentFilterInput $filter = null, array $orderBy = [], int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {

        // TODO: Criteria
        $criteria = [];

        // TODO: Criteria
        $orderBy = [];

        return new ContentResult($this->repository($domain), $type, $criteria, $orderBy, $limit, $offset, $includeDeleted, $resultFilter);
    }

    /**
     * {@inheritDoc}
     */
    public function get(Domain $domain, string $type, string $id): ?ContentInterface
    {
        return $this->repository($domain)->typedFind($type, $id);
    }

    /**
     * {@inheritDoc}
     * @throws ORMException
     */
    public function create(Domain $domain, string $type, array $inputData = [], bool $persist = false): ContentInterface {
        // TODO: Input Data
        $content = new Content($type);

        if($persist) {
            $this->em($domain)->persist($content);

            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($content);
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     * @throws ORMException
     */
    public function update(Domain $domain, string $type, ContentInterface $content, array $inputData = [], bool $persist = false): ContentInterface {

        // TODO: Update

        if($persist) {
            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($content);
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     * @throws ORMException
     */
    public function delete(Domain $domain, string $type, ContentInterface $content, bool $persist = false): ContentInterface {

        if($persist) {

            $this->em($domain)->remove($content);

            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($content);
        }

        return $content;
    }
}