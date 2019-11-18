<?php

namespace UniteCMS\AdminBundle\AdminView;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Printer;
use UniteCMS\CoreBundle\ContentType\ContentType;

class AdminView
{
    /**
     * @var string $returnType
     */
    protected $returnType;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $category
     */
    protected $category;

    /**
     * @var string $fragment
     */
    protected $fragment;

    /**
     * @var array $permissions
     */
    protected $permissions = [];

    /**
     * @var AdminViewField[]
     */
    protected $fields = [];

    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * AdminView constructor.
     *
     * @param string $returnType
     * @param FragmentDefinitionNode $definition
     * @param array $directive
     * @param string $category
     * @param ContentType $contentType
     * @param array $config
     */
    public function __construct(string $returnType, FragmentDefinitionNode $definition, array $directive, string $category, ContentType $contentType, array $config = [])
    {
        $this->returnType = $returnType;
        $this->id = $definition->name->value;
        $this->name = $directive['settings']['name'] ?? $contentType->getName();
        $this->config = $config;
        $this->category = $category;
        $this->type = $definition->typeCondition->name->value;

        // First of all, create admin fields for all content type fields, but hidden in list.
        $ctFields = [];
        foreach($contentType->getFields() as $field) {
            $ctFields[$field->getId()] = AdminViewField::fromContentTypeField($field);
        }

        // Now check the fragment and allow to override field config.
        $this->fields = [];
        foreach($definition->selectionSet->selections as $selection) {
            if($selection instanceof FieldNode) {

                $id = $selection->name->value;
                $name = null;
                $type = ($id === 'id') ? 'id' : 'text';

                // If this is an aliased field.
                if(!empty($selection->alias)) {
                    $id = $selection->alias->value;
                }

                // If this field or alias is a ct field, use that information.
                if(array_key_exists($selection->name->value, $ctFields)) {
                    $name = $ctFields[$selection->name->value]->getName();
                    $type = $ctFields[$selection->name->value]->getType();
                }

                $name = $name ?? $id;
                $field = AdminViewField::computedField($id, $type, $name);

                // If this field is a ct field, replace the ct field with this one.
                if(array_key_exists($id, $ctFields)) {
                    $field->setShowInForm($ctFields[$id]->showInForm());
                    unset($ctFields[$id]);
                }

                $this->fields[] = $field;
            }
        }

        $this->fields = array_merge($this->fields, array_values($ctFields));

        // Create client-ready fragment from given fragment.
        $definition->directives = [];
        $this->fragment = Printer::doPrint($definition);
    }

    /**
     * @return string
     */
    public function getReturnType() : string
    {
        return $this->returnType;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $permissions
     * @return $this
     */
    public function setPermissions(array $permissions) : self {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * @return array
     */
    public function getPermissions() : array {
        return $this->permissions;
    }

    /**
     * @return string
     */
    public function getFragment() : string {
        return $this->fragment;
    }

    /**
     * @return AdminViewField[]
     */
    public function getFields() : array {
        return $this->fields;
    }

    /**
     * @return string
     */
    public function getCategory() : string {
        return $this->category;
    }

    /**
     * @param string $key
     * @return null|mixed
     */
    public function getConfig(string $key) {
        return $this->config[$key] ?? null;
    }

    /**
     * @param array $config
     * @return self
     */
    public function setConfig(array $config) : self {
        $this->config = $config;
        return $this;
    }
}
