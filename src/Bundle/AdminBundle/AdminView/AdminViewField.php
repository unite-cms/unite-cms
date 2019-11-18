<?php

namespace UniteCMS\AdminBundle\AdminView;

use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class AdminViewField
{
    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var string $alias
     */
    protected $name;

    /**
     * @var string $type
     */
    protected $type;

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
     * AdminView constructor.
     *
     * @param string $id
     * @param string $type
     * @param string $name
     */
    public function __construct(string $id, string $type, string $name)
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * @param ContentTypeField $contentTypeField
     * @return static
     */
    static function fromContentTypeField(ContentTypeField $contentTypeField) : self {
        return new self(
            $contentTypeField->getId(),
            $contentTypeField->getType(),
            $contentTypeField->getName()
        );
    }

    /**
     * @param string $id
     * @param string $type
     * @param string $name
     *
     * @return static
     */
    static function computedField(string $id, string $type, string $name) : self {
        $field = new self($id, $type, $name);
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
     * @return string
     */
    public function getType() : string {
        return $this->type;
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
}
