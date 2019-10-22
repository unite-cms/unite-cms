<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;

class TestContentResult implements ContentResultInterface
{

    /**
     * @var TestContent[] $content
     */
    protected $content;

    public function __construct(array $content = [])
    {
        $this->content = $content;
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
        return $this->content;
    }
}
