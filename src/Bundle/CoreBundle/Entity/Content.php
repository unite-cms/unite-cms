<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOf;
use UniteCMS\CoreBundle\Validator\Constraints\ValidContentTranslations;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;

/**
 * Content
 *
 * @ORM\Table(name="content")
 * @ORM\Entity
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deleted", timeAware=false)
 */
class Content implements FieldableContent
{
    /**
     * @var int
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var ContentType
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\ContentType", inversedBy="content", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="content_type_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $contentType;

    /**
     * @var string
     * @Assert\Locale()
     * @ValidFieldableContentLocale(message="validation.invalid_locale")
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale;

    /**
     * @var array
     * @ValidFieldableContentData(additionalDataMessage="validation.additional_data", groups={"Default", "DELETE"})
     * @Gedmo\Versioned
     * @ORM\Column(name="data", type="json", nullable=true)
     */
    protected $data = [];

    /**
     * @var Content[]
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\Content>")
     * @Accessor(getter="geTranslations",setter="setTranslations")
     * @ValidContentTranslations(uniqueLocaleMessage="validation.unique_translations", nestedTranslationMessage="validation.nested_translations")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\Content", mappedBy="translationOf", fetch="EXTRA_LAZY")
     */
    private $translations;

    /**
     * @var Content
     * @Type("UniteCMS\CoreBundle\Entity\Content")
     * @Accessor(getter="geTranslationOf",setter="setTranslationOf")
     * @ORM\ManyToOne(targetEntity="Content", inversedBy="translations")
     * @ValidContentTranslationOf(uniqueLocaleMessage="validation.unique_translations")
     * @ORM\JoinColumn(name="translation_of_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $translationOf;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @var \DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     */
    private $deleted;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->translationOf = null;
    }

    public function __toString()
    {
        $string = '';

        // If content_label property is defined, use it.
        if (!empty($this->getContentType()) && !empty($this->getContentType()->getContentLabel())) {
            $string = $this->getContentType()->getContentLabel();

            // Find all variable placeholders in the content label.
            preg_match_all("/{([a-z0-9._]+)}/", $string, $output_array);
            if (count($output_array) == 2) {
                foreach ($output_array[1] as $value) {
                    if (($replacement = $this->findDataBySelector($value)) !== null) {
                        $string = str_replace('{'.$value.'}', $replacement, $string);
                    }
                }
            }

            return $string;
        }

        $string .= empty((string)$this->getContentType()) ? 'Content' : (string)$this->getContentType();

        if ($this->getId()) {
            $string .= ' #'.$this->getId();
        }

        return $string;
    }

    /**
     * Returns possible nested data by a selector.
     * The following fields are defined per default: 'id', 'created', 'updated', 'deleted', 'type' and 'locale'.
     * Additionally all data values can be selected. Nested values can be defined with a dot '.'.
     *
     * Examples:
     *   id
     *   created
     *   any_field
     *   any_field.any_sub_field
     *
     * @param string $selector
     * @param array $data , this param is used internal to recursively find nested values.
     *
     * @return string|null
     */
    public function findDataBySelector(string $selector, array $data = null)
    {

        // For the root call, $data is NULL. In this case we can select content root fields.
        if ($data === null) {
            $data = array_merge(
                $this->getData(),
                [
                    'id' => (string)$this->getId(),
                    'created' => ($this->getCreated() ? $this->getCreated()->format('Y-m-d H:i:s') : ''),
                    'updated' => ($this->getUpdated() ? $this->getUpdated()->format('Y-m-d H:i:s') : ''),
                    'deleted' => ($this->getDeleted() ? $this->getDeleted()->format('Y-m-d H:i:s') : ''),
                    'type' => (string)$this->getContentType(),
                    'locale' => (string)$this->getLocale(),
                ]
            );
        }

        $selector_parts = explode('.', $selector);

        // If this is the deepest component of the selector, try to return from array.
        if (count($selector_parts) == 1) {
            return isset($data[$selector]) ? (string)$data[$selector] : null;
        }

        // if this is not the deepest component try to find this field in the data array.
        $top_selector = array_shift($selector_parts);

        return !empty($data[$top_selector]) ? $this->findDataBySelector(
            implode('.', $selector_parts),
            $data[$top_selector]
        ) : null;
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
     * @param Fieldable $entity
     *
     * @return Content
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof ContentType) {
            $this->setContentType($entity);
        }

        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->getContentType();
    }

    /**
     * @param ContentType $contentType
     *
     * @return Content
     */
    public function setContentType(ContentType $contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return ContentType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return Content
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return Content
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns all translations for this element including itself.
     * @return ArrayCollection|Content[]
     */
    public function getAllTranslations()
    {
        if (!empty($this->getTranslationOf())) {
            $translations = new ArrayCollection($this->getTranslationOf()->getTranslations()->toArray());
            $translations->add($this->getTranslationOf());

            return $translations;
        }

        $translations = new ArrayCollection($this->getTranslations()->toArray());
        $translations->add($this);

        return $translations;
    }

    /**
     * @return Content[]|ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ArrayCollection|Content[] $translations
     * @return Content
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    public function addTranslation(Content $translation)
    {

        // Check if content is not already a translation.
        if (!$translation->getTranslationOf()) {

            // Check that locale is supported.
            if (in_array($translation->getLocale(), $this->getContentType()->getLocales())) {
                if (!$this->translations->contains($translation)) {
                    $this->translations->add($translation);
                    $translation->setTranslationOf($this);
                }
            }
        }

        return $this;
    }

    /**
     * @return Content
     */
    public function getTranslationOf()
    {
        return $this->translationOf;
    }

    /**
     * @param Content|null $translationOf
     * @return Content
     */
    public function setTranslationOf($translationOf)
    {
        if ($translationOf && $translationOf->getTranslationOf() != null) {
            $this->translationOf = $translationOf->getTranslationOf();
        } else {
            $this->translationOf = $translationOf;
        }

        // If this translation is not already part of it's owners translations, add it.
        if ($this->translationOf != null) {
            $this->translationOf->addTranslation($this);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return Content
     */
    public function recoverDeleted()
    {
        $this->deleted = null;

        return $this;
    }
}

