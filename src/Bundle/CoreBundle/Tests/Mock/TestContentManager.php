<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Event\ContentEvent;

class TestContentManager implements ContentManagerInterface
{
    protected $repository = [];

    public function find(Domain $domain, string $type, ContentFilterInput $filter = null, array $orderBy = [], int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null): ContentResultInterface {

        if(!isset($this->repository[$type])) {
            return null;
        }

        return new TestContentResult(array_slice(array_filter($this->repository[$type], function(TestContent $content) use ($includeDeleted) {
            return $includeDeleted || empty($content->getDeleted());
        }), $offset, $limit));
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
    }
}
