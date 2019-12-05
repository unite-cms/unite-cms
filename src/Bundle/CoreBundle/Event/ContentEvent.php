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
     * ContentEvent constructor.
     *
     * @param \UniteCMS\CoreBundle\Content\ContentInterface $content
     * @param array $previousData
     */
    public function __construct(ContentInterface $content, array $previousData = [])
    {
        $this->content = $content;
        $this->previousData = $previousData;
    }

    /**
     * @return \UniteCMS\CoreBundle\Content\ContentInterface
     */
    public function getContent() : ContentInterface {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getPreviousData() : array {
        return $this->previousData;
    }
}
