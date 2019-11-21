<?php

namespace UniteCMS\CoreBundle\Content;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;
use UniteCMS\CoreBundle\Query\ContentCriteria;

interface ContentManagerInterface
{
    /**
     * Find content for given type and criteria.
     *
     * A callable function can be passed to filter results before it will be
     * returned. If possible, filtering should be done using a ContentCriteria
     * object, however sometimes (e.g. permission checking) you need the full
     * object to filter it. In this cases the resultFilter parameter can be used.
     *
     * @param Domain $domain
     * @param string $type
     * @param ContentCriteria $criteria
     * @param bool $includeDeleted
     * @param callable|null $resultFilter
     *
     * @return ContentResultInterface
     */
    public function find(Domain $domain, string $type, ContentCriteria $criteria, bool $includeDeleted = false, ?callable $resultFilter = null) : ContentResultInterface;

    /**
     * Get a single content by id.
     *
     * @param Domain $domain
     * @param string $type
     * @param string $id
     * @param bool $includeDeleted
     *
     * @return ContentInterface|null
     */
    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false) : ?ContentInterface;

    /**
     * Start a transaction, execute $transaction() and finish transaction.
     *
     * @param Domain $domain
     * @param callable $transaction
     *
     * @return mixed, the return value of $transaction
     */
    public function transactional(Domain $domain, callable $transaction);

    /**
     * Create a new content of given type (without persisting it).
     *
     * @param Domain $domain
     * @param string $type
     *
     * @return ContentInterface
     */
    public function create(Domain $domain, string $type) : ContentInterface;

    /**
     * Update a given content with a set of FieldData (without persisting it).
     *
     * @param Domain $domain
     * @param ContentInterface $content
     * @param FieldData[]
     *
     * @return ContentInterface
     */
    public function update(Domain $domain, ContentInterface $content, array $inputData = []) : ContentInterface;

    /**
     * @param Domain $domain
     * @param ContentInterface $content
     * @param int $version
     *
     * @return ContentInterface
     *
     * @throws InvalidContentVersionException
     */
    public function revert(Domain $domain, ContentInterface $content, int $version) : ContentInterface;

    /**
     * @param Domain $domain
     * @param ContentInterface $content
     * @param int $limit
     * @param int $offset
     *
     * @return ContentRevisionInterface[]
     */
    public function revisions(Domain $domain, ContentInterface $content, int $limit = 20, int $offset = 0) : array;

    /**
     * Mark a content item as deleted (without persisting it).
     *
     * Deleted content should not be deleted, but skipped for get / find if
     * $includeDeleted is not true.
     *
     * @param Domain $domain
     * @param ContentInterface $content
     *
     * @return ContentInterface
     */
    public function delete(Domain $domain, ContentInterface $content) : ContentInterface;

    /**
     * Recover a deleted content item (without persisting it).
     *
     * @param Domain $domain
     * @param ContentInterface $content
     *
     * @return ContentInterface
     */
    public function recover(Domain $domain, ContentInterface $content) : ContentInterface;

    /**
     * Permanently remove a content item from the system (without persisting it).
     *
     * Most of the time, you will preform this action only on deleted content,
     * but it is allowed for all content.
     *
     * @param Domain $domain
     * @param ContentInterface $content
     *
     * @return ContentInterface
     */
    public function permanentDelete(Domain $domain, ContentInterface $content) : ContentInterface;

    /**
     * Flush all pending updates.
     *
     * @param Domain $domain
     */
    public function flush(Domain $domain) : void;

    /**
     * Will be called after an persist=false operations instead of flush.
     *
     * This allows cleanup and stuff.
     *
     * @param Domain $domain
     */
    public function noFlush(Domain $domain) : void;
}
