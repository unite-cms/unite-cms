<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;

class TestContentResult implements ContentResultInterface
{
    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var TestContent[] $content
     */
    protected $content;

    /**
     * @var null|callable $resultFilter
     */
    protected $resultFilter;

    public function __construct(string $type, array $content = [], ?callable $resultFilter = null)
    {
        $this->type = $type;
        $this->content = $content;
        $this->resultFilter = $resultFilter;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return count($this->content);
    }

    /**
     * @return ContentInterface[]
     */
    public function getResult(): array
    {
        return $this->resultFilter ? array_filter($this->content, $this->resultFilter) : $this->content;
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }
}
