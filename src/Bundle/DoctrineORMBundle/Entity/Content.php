<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\ContentFieldInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;

/**
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\ContentRepository")
 */
class Content implements ContentInterface
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
     * Content constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType() : string {
        return $this->type;
    }

    /**
     * @return ContentFieldInterface[]
     */
    public function getData(): array
    {
        return [];
    }

    public function getFieldData(string $fieldName): ?ContentFieldInterface
    {
        return null;
    }
}
