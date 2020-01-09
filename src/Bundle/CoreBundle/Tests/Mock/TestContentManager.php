<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;

class TestContentManager implements ContentManagerInterface
{
    protected $repository = [];
    protected $versionedData = [];
    protected $actions = [];

    /**
     * {@inheritDoc}
     */
    public function transactional(Domain $domain, callable $transaction) {
        return $transaction();
    }

    /**
     * {@inheritDoc}
     */
    public function find(Domain $domain, string $type, ContentCriteria $criteria, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {

        if(!isset($this->repository[$type])) {
            return new TestContentResult($type);
        }

        return new TestContentResult($type, array_slice(array_filter($this->repository[$type], function(TestContent $content) use ($includeDeleted) {
            return $includeDeleted || empty($content->getDeleted());
        }), $criteria->getFirstResult(), $criteria->getMaxResults()), $resultFilter);
    }

    /**
     * {@inheritDoc}
     */
    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false): ?ContentInterface {

        if(!isset($this->repository[$type][$id])) {
            return null;
        }

        if(!$includeDeleted && !empty($this->repository[$type][$id]->getDeleted())) {
            return null;
        }

        return $this->repository[$type][$id];
    }

    /**
     * {@inheritDoc}
     */
    public function create(Domain $domain, string $type): ContentInterface {
        $content = new TestContent($type);
        $this->actions[] = function() use ($content) {
            $content->setId();
            $this->repository[$content->getType()][$content->getId()] = $content;
            $this->versionedData[$content->getId()] = $this->versionedData[$content->getId()] ?? [];
            $this->versionedData[$content->getId()][] = $content->getData();
        };
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Domain $domain, ContentInterface $content, array $inputData = []): ContentInterface {
        $content->setData($inputData);
        $this->versionedData[$content->getId()] = $this->versionedData[$content->getId()] ?? [];
        $this->versionedData[$content->getId()][] = $content->getData();
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function revert(Domain $domain, ContentInterface $content, int $version) : ContentInterface {

        if(!isset($this->versionedData[$content->getId()][$version])) {
            throw new InvalidContentVersionException();
        }

        $this->actions[] = function() use ($content, $domain, $version) {
            $this->update($domain, $content, $this->versionedData[$content->getId()][$version]);
        };

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function permanentDelete(Domain $domain, ContentInterface $content): ContentInterface {
        $this->actions[] = function() use ($content) {
            unset($this->repository[$content->getType()][$content->getId()]);
        };
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Domain $domain, ContentInterface $content): ContentInterface {
        $this->actions[] = function() use ($content) {
            $content->setDeleted(new DateTime());
        };
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function recover(Domain $domain, ContentInterface $content): ContentInterface {
        $this->actions[] = function() use ($content) {
            $content->setDeleted(null);
        };
        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function revisions(Domain $domain, ContentInterface $content, int $limit = 20, int $offset = 0, array $orderBy = ['version' => 'DESC']): array {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function flush(Domain $domain): void {

        // Execute and clean all actions.
        foreach($this->actions as $action) {
            $action();
        }

        $this->actions = [];
    }

    /**
     * {@inheritDoc}
     */
    public function noFlush(Domain $domain) : void {
        $this->actions = [];
    }
}
