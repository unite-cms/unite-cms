<?php


namespace UniteCMS\CoreBundle\Content;

/**
 * Objects of this class hold a content and a field id of this content.
 *
 * This is useful if you want to pass this information together, for example to
 * a security voter to check field access.
 *
 */
class ContentField
{
    /**
     * @var ContentInterface
     */
    protected $content;

    /**
     * @var string
     */
    protected $fieldId;

    public function __construct(ContentInterface $content, string $fieldId)
    {
        $this->content = $content;
        $this->fieldId = $fieldId;
    }

    /**
     * @return ContentInterface
     */
    public function getContent() : ContentInterface {
        return $this->content;
    }

    /**
     * @return string FieldData
     */
    public function getFieldId() : string {
        return $this->fieldId;
    }
}
