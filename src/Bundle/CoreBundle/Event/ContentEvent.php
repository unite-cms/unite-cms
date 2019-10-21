<?php


namespace UniteCMS\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use UniteCMS\CoreBundle\Content\ContentInterface;

class ContentEvent extends Event
{
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

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
