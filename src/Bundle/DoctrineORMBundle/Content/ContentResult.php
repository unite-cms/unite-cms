<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\DoctrineORMBundle\Repository\ContentRepository;

class ContentResult implements ContentResultInterface
{

    /**
     * @var ContentRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var ContentCriteria
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $includeDeleted;

    /**
     * @var callable|null
     */
    protected $resultFilter;

    /**
     * ContentResult constructor.
     *
     * @param ContentRepository $repository
     * @param string $type
     * @param ContentCriteria $criteria
     * @param bool $includeDeleted
     * @param callable|null $resultFilter
     */
    public function __construct(ContentRepository $repository, string $type, ContentCriteria $criteria, bool $includeDeleted, ?callable $resultFilter = null)
    {
        $this->repository = $repository;
        $this->type = $type;
        $this->criteria = $criteria;
        $this->includeDeleted = $includeDeleted;
        $this->resultFilter = $resultFilter;
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getTotal(): int {
        return $this->repository->typedCount($this->type, $this->criteria, $this->includeDeleted);
    }

    /**
     * @return ContentInterface[]
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getResult(): array {
        $result = $this->repository->typedFindBy($this->type, $this->criteria, $this->includeDeleted);
        return $this->resultFilter ? array_filter($result, $this->resultFilter) : $result;
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }
}
