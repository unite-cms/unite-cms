<?php


namespace UniteCMS\CoreBundle\Content;

/**
 * A temporary revision content that will be created when accessing revisions.
 *
 * @package UniteCMS\CoreBundle\Content
 */
class RevisionContent extends Content
{
    /**
     * Content constructor.
     *
     * @param string $type
     * @param array $data
     */
    public function __construct(string $type, array $data)
    {
        parent::__construct($type);
        $this->data = $data;
    }
}
