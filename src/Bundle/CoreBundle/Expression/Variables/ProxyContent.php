<?php


namespace UniteCMS\CoreBundle\Expression\Variables;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;

class ProxyContent
{
    protected $content = null;

    public function __construct(?ContentInterface $content = null) {
        $this->content = $content;
    }

    /**
     * @param ProxyContent|null $content
     * @return bool
     */
    public function isEqualTo(?ProxyContent $content = null) : bool {
        return $this->id() === $content->id();
    }

    /**
     * Returns true if this content is of $type.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isType(string $type) : bool {
        return strtolower($type) === strtolower($this->type());
    }

    /**
     * @return bool
     */
    public function isNew() : bool {
        return $this->content ? $this->content->isNew() : true;
    }

    /**
     * @return string
     */
    public function type() : string {

        if(!$this->content) {
            return '';
        }

        return $this->content->getType();
    }

    /**
     * @param string $fieldName
     * @param mixed|null $defaultValue
     *
     * @return FieldData|null
     */
    public function get(string $fieldName, $defaultValue = null) {
        if(!$this->content) {
            return $defaultValue;
        }

        $fieldNameParts = explode('.', $fieldName);
        $fieldData = $this->content->getFieldData($fieldNameParts[0]);
        return $fieldData ? ($fieldData->resolveData(count($fieldNameParts) > 1 ? $fieldNameParts[1] : '', $defaultValue)) : $fieldNameParts;
    }

    /**
     * @return string
     */
    public function id() : string {
        return $this->content ? '' . $this->content->getId() : '';
    }
}
