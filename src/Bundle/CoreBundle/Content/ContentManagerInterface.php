<?php


namespace UniteCMS\CoreBundle\Content;

use UniteCMS\CoreBundle\Domain\Domain;

interface ContentManagerInterface
{
    public function find(Domain $domain, string $type, ContentFilterInput $filter = null, array $orderBy = [], int $limit = 20, int $offset = 0, bool $includeDeleted = false, ?callable $resultFilter = null) : ContentResultInterface;
    public function get(Domain $domain, string $type, string $id) : ?ContentInterface;

    public function create(Domain $domain, string $type) : ContentInterface;
    public function update(Domain $domain, ContentInterface $content, array $inputData = [], bool $persist = false) : ContentInterface;
    public function delete(Domain $domain, string $type, ContentInterface $content, bool $persist = false) : ContentInterface;
}
