<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\DoctrineORMBundle\Repository\ContentRepository;

class ContentResult implements ContentResultInterface
{

    /**
     * @var ContentRepository
     */
    protected $repository;

    /**
     * @var ORMQueryCriteria
     */
    protected $criteria;

    /**
     * @var callable|null
     */
    protected $resultFilter;

    /**
     * ContentResult constructor.
     *
     * @param ContentRepository $repository
     * @param ORMQueryCriteria $criteria
     * @param callable|null $resultFilter
     */
    public function __construct(ContentRepository $repository, ORMQueryCriteria $criteria, ?callable $resultFilter = null)
    {
        $this->repository = $repository;
        $this->criteria = $criteria;
        $this->resultFilter = $resultFilter;
    }

    /**
     * @return int
     */
    public function getTotal(): int {
        return $this->repository->typedCount($this->criteria);
    }

    /**
     * @return ContentInterface[]
     */
    public function getResult(): array {
        $result = $this->repository->typedFindBy($this->criteria);
        return $this->resultFilter ? array_filter($result, $this->resultFilter) : $result;
    }
}
