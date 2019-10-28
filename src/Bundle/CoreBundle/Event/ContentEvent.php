<?php


namespace UniteCMS\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use UniteCMS\CoreBundle\Content\ContentInterface;

class ContentEvent extends Event
{
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';
    const REVERT = 'REVERT';
    const DELETE = 'DELETE';
    const RECOVER = 'RECOVER';

    protected $content;

    /**
     * ContentEvent constructor.
     *
     * @param \UniteCMS\CoreBundle\Content\ContentInterface $content
     */
    public function __construct(ContentInterface $content)
    {
        $this->content = $content;
    }

    /**
     * @return \UniteCMS\CoreBundle\Content\ContentInterface
     */
    public function getContent() : ContentInterface {
        return $this->content;
    }
}
