<?php


namespace UniteCMS\CoreBundle\Model;

use InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;

/**
 * This model is just a tiny wrapper to pass fieldable fields together with it's content to the FieldableFiledVoter.
 */
class FieldableFieldContent
{
    /**
     * @var FieldableField
     */
    protected $field;

    /**
     * @var FieldableContent
     */
    protected $content;

    public function __construct(FieldableField $field, FieldableContent $content = null)
    {
        $this->setField($field);
        $this->setContent($content);
    }

    /**
     * @return FieldableField
     */
    public function getField(): FieldableField
    {
        return $this->field;
    }

    /**
     * @param FieldableField $field
     */
    public function setField(FieldableField $field): void
    {
        if(!empty($this->content) && $field->getEntity() !== $this->content->getEntity()) {
            throw new InvalidArgumentException('Fieldable of field and entity must be the same object.');
        }

        $this->field = $field;
    }

    /**
     * @return FieldableContent
     */
    public function getContent(): ?FieldableContent
    {
        return $this->content;
    }

    /**
     * @param FieldableContent $content
     */
    public function setContent(FieldableContent $content = null): void
    {
        if(!empty($this->field) && !empty($contentd) && !empty($content->getEntity()) && $this->field->getEntity() !== $content->getEntity()) {
            throw new InvalidArgumentException('Fieldable of field and entity must be the same object.');
        }

        $this->content = $content;
    }
}
