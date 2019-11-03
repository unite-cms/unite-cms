<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;

class TestContentManager implements ContentManagerInterface
{
    protected $versionedData = [];
    protected $repository = [];

    public function find(Domain $domain, string $type, ContentCriteria $criteria, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {

        if(!isset($this->repository[$type])) {
            return null;
        }



        return new TestContentResult(array_slice(array_filter($this->repository[$type], function(TestContent $content) use ($includeDeleted) {
            return $includeDeleted || empty($content->getDeleted());
        }), $criteria->getFirstResult(), $criteria->getMaxResults()), $resultFilter);
    }

    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false): ?ContentInterface {

        if(!isset($this->repository[$type][$id])) {
            return null;
        }

        if(!$includeDeleted && !empty($this->repository[$type][$id]->getDeleted())) {
            return null;
        }

        return $this->repository[$type][$id];
    }

    public function create(Domain $domain, string $type): ContentInterface {
        return new TestContent($type);
    }

    public function update(Domain $domain, ContentInterface $content, array $inputData = []): ContentInterface {
        return $content->setData($inputData);
    }

    /**
     * {@inheritDoc}
     */
    public function revert(Domain $domain, ContentInterface $content, int $version) : ContentInterface {

        if(!isset($this->versionedData[$content->getId()][$version])) {
            throw new InvalidContentVersionException();
        }

        $content->setData($this->versionedData[$content->getId()][$version]);
        return $content;
    }

    public function delete(Domain $domain, ContentInterface $content): ContentInterface {
        return $content;
    }

    public function recover(Domain $domain, ContentInterface $content): ContentInterface {
        return $content;
    }

    public function persist(Domain $domain, ContentInterface $content, string $persistType): void {
        $this->repository[$content->getType()] = $this->repository[$content->getType()] ?? [];

        if($persistType === ContentEvent::CREATE) {
            $content->setId();
            $this->repository[$content->getType()][$content->getId()] = $content;
        }

        else if($persistType === ContentEvent::DELETE) {

            if($content->getDeleted()) {
                unset($this->repository[$content->getType()][$content->getId()]);
            } else {
                $content->setDeleted(new DateTime());
            }
        }

        else if ($persistType === ContentEvent::RECOVER) {
            $content->setDeleted(null);
        }

        $this->versionedData[$content->getId()] = $this->versionedData[$content->getId()] ?? [];
        $this->versionedData[$content->getId()][] = $content->getData();
    }

    /**
     * {@inheritDoc}
     */
    public function revisions(Domain $domain, ContentInterface $content, int $limit = 20): array {
        return [];
    }
}
