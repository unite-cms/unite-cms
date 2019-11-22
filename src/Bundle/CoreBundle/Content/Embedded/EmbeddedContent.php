<?php


namespace UniteCMS\CoreBundle\Content\Embedded;

use DateTime;
use UniteCMS\CoreBundle\Content\BaseContent;

class EmbeddedContent extends BaseContent
{

    /**
     * EmbeddedContent constructor.
     *
     * @param string $id
     * @param string $type
     * @param array $data
     */
    public function __construct(string $id, string $type, array $data = [])
    {
        $this->id = $id;
        $this->data = $data;
        parent::__construct($type);
    }

    /**
     * {@inheritDoc}
     */
    public function isNew() : bool
    {
        // Will always return false
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleted(): ?DateTime
    {
        // Will always return null
        return null;
    }
}
