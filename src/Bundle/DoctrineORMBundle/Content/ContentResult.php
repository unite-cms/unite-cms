<?php

namespace UniteCMS\DoctrineORMBundle\Content;

use UniteCMS\CoreBundle\Content\ContentFilterInput;
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
     * @var string $type
     */
    protected $type;

    /**
     * @var ContentFilterInput
     */
    protected $filter;

    /**
     * @var array
     */
    protected $orderBy;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

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
     * @param ContentFilterInput $filter
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @param bool $includeDeleted
     * @param callable|null $resultFilter
     */
    public function __construct(ContentRepository $repository, string $type, ContentFilterInput $filter = null, array $orderBy = null, int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null)
    {
        $this->repository = $repository;
        $this->type = $type;
        $this->filter = $filter;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->includeDeleted = $includeDeleted;
        $this->resultFilter = $resultFilter;
    }

    /**
     * @return int
     */
    public function getTotal(): int {
        return $this->repository->typedCount($this->type, $this->filter, $this->includeDeleted);
    }

    /**
     * @return ContentInterface[]
     */
    public function getResult(): array {
        $result = $this->repository->typedFindBy($this->type, $this->filter, $this->orderBy, $this->limit, $this->offset, $this->includeDeleted);
        return $this->resultFilter ? array_filter($result, $this->resultFilter) : $result;
    }
}
