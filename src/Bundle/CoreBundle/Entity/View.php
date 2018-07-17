<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Validator\Constraints\ValidIdentifier;
use UniteCMS\CoreBundle\Validator\Constraints\ViewType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidViewSettings;
use UniteCMS\CoreBundle\View\ViewSettings;

/**
 * View
 *
 * @UniqueEntity(fields={"identifier", "contentType"}, message="identifier_already_taken")
 * @ORM\Table(name="view")
 * @ORM\Entity(repositoryClass="UniteCMS\CoreBundle\Repository\ViewRepository")
 * @ExclusionPolicy("all")
 */
class View
{
    const DEFAULT_VIEW_IDENTIFIER = "all";
    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete'];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="title", type="string", length=255)
     * @Expose
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ValidIdentifier(message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\View::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ViewType(message="invalid_view_type")
     * @ORM\Column(name="type", type="string", length=255)
     * @Expose
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Expose
     */
    private $description;

    /**
     * @var string
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_-]+$/", message="invalid_characters")
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     * @Expose
     */
    private $icon;

    /**
     * @var ContentType
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\ContentType", inversedBy="views", fetch="EXTRA_LAZY")
     */
    private $contentType;

    /**
     * @var ViewSettings
     *
     * @ORM\Column(name="settings", type="object", nullable=true)
     * @ValidViewSettings()
     * @Assert\NotNull(message="not_null")
     * @Type("UniteCMS\CoreBundle\View\ViewSettings")
     * @Expose
     */
    private $settings;

    public function __construct()
    {
        $this->settings = new ViewSettings();
    }

    public function __toString()
    {
        return ''.$this->getTitle();
    }

    /**
     * This function sets all structure fields from the given entity.
     *
     * @param View $view
     * @return View
     */
    public function setFromEntity(View $view)
    {
        $this
            ->setTitle($view->getTitle())
            ->setIdentifier($view->getIdentifier())
            ->setType($view->getType())
            ->setDescription($view->getDescription())
            ->setIcon($view->getIcon())
            ->setSettings($view->getSettings());

        return $this;
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return View
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return View
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return View
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return View
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return View
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set icon
     *
     * @param string $icon
     *
     * @return View
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return ContentType
     */
    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    /**
     * @param ContentType $contentType
     *
     * @return View
     */
    public function setContentType(ContentType $contentType)
    {
        $this->contentType = $contentType;
        $contentType->addView($this);

        return $this;
    }

    /**
     * Set settings
     *
     * @param ViewSettings $settings
     *
     * @return View
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return ViewSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }
}

