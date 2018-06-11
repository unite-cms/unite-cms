<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use UniteCMS\CoreBundle\View\Types\TableViewType;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Accessor;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Validator\Constraints\DefaultViewType;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;

/**
 * ContentType
 *
 * @ORM\Table(name="content_type")
 * @ORM\Entity(repositoryClass="UniteCMS\CoreBundle\Repository\ContentTypeRepository")
 * @UniqueEntity(fields={"identifier", "domain"}, message="identifier_already_taken")
 * @ExclusionPolicy("all")
 */
class ContentType implements Fieldable
{
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
     * @Assert\Regex(pattern="/^[a-z0-9-]+$/", message="invalid_characters")
     * @ReservedWords(message="reserved_identifier", reserved="UniteCMS\CoreBundle\Entity\ContentType::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
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
     * @var string
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="content_label", type="string", length=255, nullable=true)
     * @Expose
     */
    private $contentLabel = '{type} #{id}';

    /**
     * @var Domain
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="contentTypes")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $domain;

    /**
     * @var ContentTypeField[]
     * @Assert\Valid()
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\ContentTypeField>")
     * @Accessor(getter="getFields",setter="setFields")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\ContentTypeField", mappedBy="contentType", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @ORM\OrderBy({"weight": "ASC"})
     * @Expose
     */
    private $fields;

    /**
     * @var View[]
     *
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\View>")
     * @Assert\Valid()
     * @DefaultViewType(message="missing_default_view")
     * @Accessor(getter="getViews",setter="setViews")
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\View", mappedBy="contentType", cascade={"persist", "remove", "merge"}, indexBy="identifier", orphanRemoval=true)
     * @Expose
     */
    private $views;

    /**
     * @var array
     * @ValidPermissions(callbackAttributes="allowedPermissionKeys", message="invalid_selection")
     * @ORM\Column(name="permissions", type="array", nullable=true)
     * @Expose
     */
    private $permissions;

    /**
     * @var array
     * @Assert\All({
     *     @Assert\Locale(),
     *     @Assert\NotBlank()
     * })
     * @ORM\Column(name="locales", type="array", nullable=true)
     * @Type("array<string>")
     * @Expose
     */
    private $locales;

    /**
     * @var int
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    /**
     * @var Content[]|ArrayCollection
     * @Type("ArrayCollection<UniteCMS\CoreBundle\Entity\Content>")
     * @Assert\Valid()
     *
     * TODO: Checking that all the content is valid will become very expensive for large content sets. We most likely will need another approach.
     *
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\Content", mappedBy="contentType", fetch="EXTRA_LAZY", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    private $content;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->views = new ArrayCollection();
        $this->locales = [];
        $this->addDefaultView();
        $this->addDefaultPermissions();
    }

    public function __toString()
    {
        return ''.$this->title;
    }

    /**
     * Each ContentType must have a "all" view. This function adds it to the ArrayCollection.
     */
    private function addDefaultView()
    {
        $defaultView = new View();
        $defaultView
            ->setType(TableViewType::getType())
            ->setTitle('All')
            ->setIdentifier(View::DEFAULT_VIEW_IDENTIFIER);
        $this->addView($defaultView);
    }

    private function addDefaultPermissions()
    {
        $this->permissions[ContentVoter::VIEW] = 'true';
        $this->permissions[ContentVoter::LIST] = 'true';
        $this->permissions[ContentVoter::CREATE] = 'member.type == "editor"';
        $this->permissions[ContentVoter::UPDATE] = 'member.type == "editor"';
        $this->permissions[ContentVoter::DELETE] = 'member.type == "editor"';
    }

    public function allowedPermissionKeys(): array
    {
        return array_merge(ContentVoter::ENTITY_PERMISSIONS, ContentVoter::BUNDLE_PERMISSIONS);
    }

    /**
     * This function sets all structure fields from the given entity and calls setFromEntity for all updated
     * views and fields.
     *
     * @param ContentType $contentType
     * @return ContentType
     */
    public function setFromEntity(ContentType $contentType)
    {
        $this
            ->setTitle($contentType->getTitle())
            ->setIdentifier($contentType->getIdentifier())
            ->setWeight($contentType->getWeight())
            ->setIcon($contentType->getIcon())
            ->setContentLabel($contentType->getContentLabel())
            ->setPermissions($contentType->getPermissions())
            ->setDescription($contentType->getDescription())
            ->setLocales($contentType->getLocales());

        // Fields to delete
        foreach (array_diff($this->getFields()->getKeys(), $contentType->getFields()->getKeys()) as $field) {
            $this->getFields()->remove($field);
        }

        // Fields to add
        foreach (array_diff($contentType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->addField($contentType->getFields()->get($field));
        }

        // Fields to update
        foreach (array_intersect($contentType->getFields()->getKeys(), $this->getFields()->getKeys()) as $field) {
            $this->getFields()->get($field)->setFromEntity($contentType->getFields()->get($field));
        }

        // Views to delete
        foreach (array_diff(
                     $this->getViews()->getKeys(),
                     $contentType->getViews()->getKeys()
                 ) as $view) {
            $this->getViews()->remove($view);
        }

        // Views to add
        foreach (array_diff(
                     $contentType->getViews()->getKeys(),
                     $this->getViews()->getKeys()
                 ) as $view) {
            $this->addView($contentType->getViews()->get($view));
        }

        // Views to update
        foreach (array_intersect(
                     $contentType->getViews()->getKeys(),
                     $this->getViews()->getKeys()
                 ) as $view) {
            $this->getViews()->get($view)->setFromEntity($contentType->getViews()->get($view));
        }

        return $this;
    }

    /**
     * After deserializing a content type, field weights must be initialized.
     *
     * @Serializer\PostDeserialize
     */
    public function initWeight() {
        $weight = 0;

        foreach($this->getFields() as $field) {
            $field->setWeight($weight);
            $weight++;
        }
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return ContentType
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
     * @return ContentType
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
     * @return ContentType
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
     * Set description
     *
     * @param string $description
     *
     * @return ContentType
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
     * @return ContentType
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
     * @param string $contentLabel
     *
     * @return ContentType
     */
    public function setContentLabel($contentLabel)
    {
        $this->contentLabel = $contentLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentLabel()
    {
        return $this->contentLabel;
    }

    /**
     * @return Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     *
     * @return ContentType
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        $domain->addContentType($this);

        return $this;
    }

    /**
     * @return ContentTypeField[]|ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param ContentTypeField[]|ArrayCollection $fields
     *
     * @return ContentType
     */
    public function setFields($fields)
    {
        $this->fields->clear();
        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * @param FieldableField $field
     *
     * @return ContentType
     */
    public function addField(FieldableField $field)
    {
        if (!$field instanceof ContentTypeField) {
            throw new \InvalidArgumentException("'$field' is not a ContentTypeField.");
        }

        if (!$this->fields->containsKey($field->getIdentifier())) {
            $this->fields->set($field->getIdentifier(), $field);
            $field->setContentType($this);

            if($field->getWeight() === null) {
                $field->setWeight($this->fields->count() - 1);
            }
        }

        return $this;
    }

    /**
     * @return View[]|ArrayCollection
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param $key
     * @return View
     */
    public function getView($key)
    {
        foreach ($this->getViews() as $view) {
            if ($view->getIdentifier() === $key) {
                return $view;
            }
        }

        throw new \InvalidArgumentException("View with key '$key' was not found.");
    }

    /**
     * @param View[]|ArrayCollection $views
     *
     * @return ContentType
     */
    public function setViews($views)
    {
        $this->views->clear();

        $includes_all = false;

        foreach ($views as $view) {
            if ($view->getIdentifier() == 'all') {
                $includes_all = true;
            }
        }

        if (!$includes_all) {
            $this->addDefaultView();
        }

        foreach ($views as $view) {
            $this->addView($view);
        }

        return $this;
    }

    /**
     * @param View $view
     *
     * @return ContentType
     */
    public function addView(View $view)
    {
        if (!$this->views->containsKey($view->getIdentifier())) {
            $this->views->set($view->getIdentifier(), $view);
            $view->setContentType($this);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param array $permissions
     *
     * @return ContentType
     */
    public function setPermissions($permissions)
    {
        $this->permissions = [];
        $this->addDefaultPermissions();

        foreach ($permissions as $attribute => $expression) {
            $this->addPermission($attribute, $expression);
        }

        return $this;
    }

    public function addPermission($attribute, string $expression)
    {
        $this->permissions[$attribute] = $expression;
    }

    /**
     * @return array
     */
    public function getLocales(): array
    {
        return $this->locales ?? [];
    }

    /**
     * @param array $locales
     *
     * @return ContentType
     */
    public function setLocales(array $locales)
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return ContentType
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return ArrayCollection|Content[]
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootEntity(): Fieldable
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierPath($delimiter = '/')
    {
        return $this->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentEntity()
    {
        return null;
    }
}

