<?php

namespace UniteCMS\AdminBundle\AdminView;

use Doctrine\Common\Collections\ArrayCollection;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class AdminViewField
{
    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var null|string $description
     */
    protected $description = null;

    /**
     * @var string $fieldType
     */
    protected $fieldType;

    /**
     * @var bool $isListOf
     */
    protected $isListOf = false;

    /**
     * @var bool $isNonNull
     */
    protected $isNonNull = false;

    /**
     * @var bool $required
     */
    protected $required = false;

    /**
     * @var bool $showInList
     */
    protected $showInList = false;

    /**
     * @var bool $showInForm
     */
    protected $showInForm = true;

    /**
     * @var string|null $formGroup
     */
    protected $formGroup = null;

    /**
     * @var ArrayCollection $config
     */
    protected $config;

    /**
     * @var array
     */
    protected $directives = [];

    /**
     * AdminView constructor.
     *
     * @param string $id
     * @param string $type
     * @param string $fieldType
     * @param string $name
     * @param bool $isListOf
     * @param bool $isNonNull
     * @param bool $required
     * @param string $description
     * @param array|ArrayCollection $config
     * @param array $directives
     */
    public function __construct(string $id, string $type, string $fieldType, string $name, bool $isListOf = false, bool $isNonNull = false, bool $required = false, ?string $description = null, $config = null, array $directives = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->fieldType = $fieldType;
        $this->name = $name;
        $this->isListOf = $isListOf;
        $this->isNonNull = $isNonNull;
        $this->required = $required;
        $this->description = $description;
        $this->config = $config ? (is_array($config) ? new ArrayCollection($config) : $config) : new ArrayCollection();
        $this->directives = $directives;
    }

    /**
     * @param ContentTypeField $contentTypeField
     * @return static
     */
    static function fromContentTypeField(ContentTypeField $contentTypeField) : self {
        return new self(
            $contentTypeField->getId(),
            $contentTypeField->getId(),
            $contentTypeField->getType(),
            $contentTypeField->getName(),
            $contentTypeField->isListOf(),
            $contentTypeField->isNonNull(),
            $contentTypeField->isRequired(),
            $contentTypeField->getDescription()
        );
    }

    /**
     * @param string $id
     * @param string $type
     * @param string $fieldType
     * @param string $name
     *
     * @return static
     */
    static function computedField(string $id, string $type, string $fieldType, string $name) : self {
        $field = new self($id, $type, $fieldType, $name);
        $field->setShowInList(true);
        $field->setShowInForm(false);
        return $field;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * @param string $fieldType
     * @return $this
     */
    public function setFieldType(string $fieldType): self
    {
        $this->fieldType = $fieldType;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldType() : string {
        return $this->fieldType;
    }

    /**
     * @param bool $showInList
     * @return $this
     */
    public function setShowInList(bool $showInList) : self {
        $this->showInList = $showInList;
        return $this;
    }

    /**
     * @return bool
     */
    public function showInList() : bool {
        return $this->showInList;
    }

    /**
     * @param bool $showInForm
     * @return $this
     */
    public function setShowInForm(bool $showInForm) : self {
        $this->showInForm = $showInForm;
        return $this;
    }

    /**
     * @return bool
     */
    public function showInForm() : bool {
        return $this->showInForm;
    }

    /**
     * @param string|null $formGroup
     * @return $this
     */
    public function setFormGroup(?string $formGroup = null) : self {
        $this->formGroup = $formGroup;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFormGroup() : ?string {
        return $this->formGroup;
    }

    /**
     * @param bool $isNonNull
     * @return self
     */
    public function setIsNonNull(bool $isNonNull): self
    {
        $this->isNonNull = $isNonNull;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNonNull() : bool {
        return $this->isNonNull;
    }

    /**
     * @param bool $required
     * @return self
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired() : bool {
        return $this->required;
    }

    /**
     * @param bool $isListOf
     * @return self
     */
    public function setIsListOf(bool $isListOf): self
    {
        $this->isListOf = $isListOf;
        return $this;
    }

    /**
     * @return bool
     */
    public function isListOf() : bool {
        return $this->isListOf;
    }

    /**
     * @return ArrayCollection
     */
    public function getConfig(): ArrayCollection
    {
        return $this->config;
    }

    /**
     * @param ArrayCollection $config
     * @return self
     */
    public function setConfig(ArrayCollection $config): self {
        $this->config = $config;
        return $this;
    }

    /**
     * @return array
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * @param array $directives
     * @return self
     */
    public function setDirectives(array $directives): self
    {
        $this->directives = $directives;
        return $this;
    }
}
