<?php


namespace UniteCMS\CoreBundle\Field;

use GraphQL\Language\AST\DirectiveNode;
use InvalidArgumentException;

class FieldTypeManager
{
    /**
     * @var FieldTypeInterface[]
     */
    protected $fieldTypes = [];

    /**
     * @param \GraphQL\Language\AST\DirectiveNode $directive
     * @return string|null
     */
    static function fieldDirectiveType(DirectiveNode $directive) : ?string {
        foreach($directive->arguments as $argument) {
            if($argument->name->value === 'type') {
                return (string)$argument->value->value;
            }
        }
        return null;
    }

    /**
     * @param \GraphQL\Language\AST\DirectiveNode $directive
     * @return array
     */
    static function fieldDirectiveSettings(DirectiveNode $directive) : array {
        foreach($directive->arguments as $argument) {
            if($argument->name->value === 'settings') {
                return json_decode((string)$argument->value->value);
            }
        }
        return [];
    }

    /**
     * @param FieldTypeInterface $fieldType
     *
     * @return FieldTypeManager
     */
    public function registerFieldType(FieldTypeInterface $fieldType) : self
    {
        if (!isset($this->fieldTypes[$fieldType::getType()])) {
            $this->fieldTypes[$fieldType::getType()] = $fieldType;
        }
        return $this;
    }

    /**
     * @return FieldTypeInterface[]
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasFieldType($key): bool
    {
        return array_key_exists($key, $this->fieldTypes);
    }

    /**
     * @param $key
     *
     * @return \UniteCMS\CoreBundle\Field\FieldTypeInterface
     */
    public function getFieldType($key): FieldTypeInterface
    {
        if (!$this->hasFieldType($key)) {
            throw new InvalidArgumentException(sprintf('Invalid field type "%s".', $key));
        }
        return $this->fieldTypes[$key];
    }
}
