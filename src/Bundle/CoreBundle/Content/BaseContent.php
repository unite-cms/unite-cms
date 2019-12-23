<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A base content class that implements ContentInterface.
 *
 * @package UniteCMS\CoreBundle\Content
 */
abstract class BaseContent implements ContentInterface
{
    /**
     * @var string|null
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var FieldData[]
     */
    protected $data = [];

    /**
     * @var null|DateTime
     */
    protected $deleted = null;

    /**
     * @var DateTime
     */
    protected $created;

    /**
     * @var DateTime
     */
    protected $updated;

    /**
     * @var string|null
     * @Assert\Locale
     */
    protected $locale = null;

    /**
     * @var ContentInterface $translate
     */
    protected $translate = null;

    /**
     * @var Collection|ContentInterface[] $translations
     */
    protected $translations;

    /**
     * Content constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
        $this->created = new DateTime('now');
        $this->updated = new DateTime('now');
        $this->translations = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function isNew() : bool {
        return empty($this->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if(!is_array($this->data)) {
            $this->data = [];
        }

        return $this->data;
    }

    /**
     * @param FieldData[] $data
     * @return self
     */
    public function setData(array $data) : ContentInterface
    {
        $this->data = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        if($fieldName === 'locale') {
            return new FieldData($this->getLocale());
        }

        return isset($this->data[$fieldName]) ? $this->data[$fieldName] : null;
    }

    /**
     * @param DateTime|null $deleted
     * @return self
     */
    public function setDeleted(?DateTime $deleted = null) : ContentInterface {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleted(): ?DateTime
    {
        return $this->deleted;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return self
     */
    public function setCreated(DateTime $created): ContentInterface
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     * @return self
     */
    public function setUpdated(DateTime $updated): ContentInterface
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale = null) : void
    {
        $this->locale = $locale;
    }

    /**
     * @return ContentInterface
     */
    public function getTranslate(): ContentInterface
    {
        return $this->translate;
    }

    /**
     * @param ContentInterface $translate
     */
    public function setTranslate(?ContentInterface $translate = null) : void
    {
        $this->translate = $translate;
    }

    /**
     * @return ContentInterface[]|Collection
     */
    public function getTranslations() : Collection
    {
        if($this->translate) {
            return $this->translate->getTranslations();
        }

        return $this->translations;
    }

    /**
     * @param ContentInterface $translation
     */
    public function addTranslation(ContentInterface $translation) : void {
        if(!$this->translations->contains($translation)) {
            $this->translations[] = $translation;

        }
    }
}
