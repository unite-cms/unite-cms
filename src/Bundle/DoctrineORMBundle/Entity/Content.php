<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\Content as BaseContent;

/**
 * @ORM\Table(name="unite_content")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\ContentRepository")
 */
class Content extends BaseContent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data = [];

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deleted = null;

    /**
     * @var bool
     */
    protected $markedAsNew = false;

    /**
     * @param bool $new
     *
     * @return $this
     */
    public function markAsNew($new = true) : self {
        $this->markedAsNew = $new;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isNew() : bool {
        return parent::isNew() ?? $this->markedAsNew;
    }
}
