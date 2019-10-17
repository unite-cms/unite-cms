<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\DoctrineORMBundle\Entity\Content;

class ContentManager implements ContentManagerInterface
{
    const ENTITY = Content::class;

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
     */
    public function create(Domain $domain, string $type, array $inputData = [], bool $persist = false): ContentInterface {

        $content = new Content($type);
        $content->setData($inputData);

        if($persist) {
            $this->em($domain)->persist($content);

            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($content);
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Domain $domain, string $type, ContentInterface $content, array $inputData = [], bool $persist = false): ContentInterface {

        $content->setData($inputData);

        if($persist) {
            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($content);
        }

        return $content;
    }

    /**
     * {@inheritDoc}
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