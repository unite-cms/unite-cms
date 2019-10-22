<?php


namespace UniteCMS\CoreBundle\Content;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\InvalidContentVersionException;

interface ContentManagerInterface
{
    public function find(Domain $domain, string $type, ContentFilterInput $filter = null, array $orderBy = [], int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null) : ContentResultInterface;
    public function get(Domain $domain, string $type, string $id, bool $includeDeleted = false) : ?ContentInterface;

    public function create(Domain $domain, string $type) : ContentInterface;

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

    public function delete(Domain $domain, ContentInterface $content) : ContentInterface;
    public function recover(Domain $domain, ContentInterface $content) : ContentInterface;

    public function persist(Domain $domain, ContentInterface $content, string $persistType) : void;
}
