<?php


namespace UniteCMS\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use UniteCMS\CoreBundle\Content\ContentInterface;

abstract class ContentEvent extends Event
{
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';
    const REVERT = 'REVERT';
    const DELETE = 'DELETE';
    const PERMANENT_DELETE = 'PERMANENT_DELETE';
    const RECOVER = 'RECOVER';

    /**
     * @var array
     */
    protected $previousData;

    /**
     * @var ContentInterface
     */
    protected $content;

    /**
     * @var string|null
     */
    protected $contentId;

    /**
     * ContentEvent constructor.
     *
     * @param ContentInterface $content
     * @param array $previousData
     */
    public function __construct(ContentInterface $content, array $previousData = [])
    {
        $this->content = $content;
        $this->contentId = $content->getId();
        $this->previousData = $previousData;
    }

    /**
     * @return ContentInterface
     */
    public function getContent() : ContentInterface {
        return $this->content;
    }

    /**
     * Returns the content id, that was taken from content when this event was created.
     *
     * This can help to track the content, even when it was deleted and $content->getId() is null.
     *
     * @return string|null
     */
    public function getContentId() : ?string {
        return $this->contentId;
    }

    /**
     * @return array
     */
    public function getPreviousData() : array {
        return $this->previousData;
    }
}
