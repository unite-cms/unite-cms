<?php

namespace UniteCMS\AdminBundle\AdminView;

use Doctrine\Common\Collections\ArrayCollection;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Printer;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Field\Types\PasswordType;
use UniteCMS\CoreBundle\GraphQL\Util;

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
     * @var string $titlePattern
     */
    protected $titlePattern = '{{^name}}{{^username}}{{ title }}{{/username}}{{/name}}{{^title}}{{^username}}{{ name }}{{/username}}{{/title}}{{^name}}{{^title}}{{username}}{{/title}}{{/name}}{{^name}}{{^title}}{{^username}}{{ _name }} {{ _category }}{{#_meta.id }}: {{ _meta.id }}{{/_meta.id}}{{/username}}{{/title}}{{/name}}';

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
     * @var ArrayCollection|array $config
     */
    protected $config;

    /**
     * AdminView constructor.
     *
     * @param string $returnType
     * @param FragmentDefinitionNode $definition
     * @param array $directive
     * @param string $category
     * @param ContentType $contentType
     * @param array|ArrayCollection $config
     */
    public function __construct(string $returnType, string $category, ContentType $contentType, ?FragmentDefinitionNode $definition = null, ?array $directive = null, $config = null)
    {
        $this->returnType = $returnType;
        $this->name = empty($directive['settings']['name']) ? $contentType->getName() : $directive['settings']['name'];
        $this->titlePattern = empty($directive['settings']['titlePattern']) ? $this->titlePattern : $directive['settings']['titlePattern'];
        $this->config = $config;
        $this->category = $category;
        $this->config = $config ? (is_array($config) ? new ArrayCollection($config) : $config) : new ArrayCollection();

        // First of all, create admin fields for all content type fields, but hidden in list.
        $ctFields = [];
        foreach($contentType->getFields() as $field) {

            // Special handle password fields.
            if($field->getType() === PasswordType::getType()) {
                continue;
            }

            $ctFields[$field->getId()] = AdminViewField::fromContentTypeField($field);
        }

        // If we created this adminView without a fragment definition.
        if(!$definition) {
            $this->id = $contentType->getId() . 'defaultAdminView';
            $this->type = $contentType->getId();
            $this->fields = array_merge([
                AdminViewField::computedField('id', 'id', 'id', '#'),
            ], array_values($ctFields));
            $this->fragment = sprintf('fragment %s on %s { id }', $this->id, $this->type);
            return;
        }

        // If we create this adminView based on a fragment definition.
        $this->id = $definition->name->value;
        $this->type = $definition->typeCondition->name->value;

        // Now check the fragment and allow to override field config.
        $this->fields = [];
        foreach($definition->selectionSet->selections as $selection) {
            if($selection instanceof FieldNode) {

                $id = $selection->name->value;
                $type = $id;
                $name = null;
                $fieldType = ($id === 'id') ? 'id' : 'text';

                // If this is an aliased field.
                if(!empty($selection->alias)) {
                    $id = $selection->alias->value;
                }

                // If this field or alias is a ct field, use that information.
                if(array_key_exists($selection->name->value, $ctFields)) {
                    $name = $ctFields[$selection->name->value]->getName();
                    $fieldType = $ctFields[$selection->name->value]->getFieldType();
                }

                $name = $name ?? $id;
                $field = AdminViewField::computedField($id, $type, $fieldType, $name);

                // If this field is a ct field, replace the ct field with this one.
                if(array_key_exists($id, $ctFields)) {
                    $field
                        ->setShowInForm($ctFields[$id]->showInForm())
                        ->setIsListOf($ctFields[$id]->isListOf())
                        ->setIsNonNull($ctFields[$id]->isNonNull())
                        ->setRequired($ctFields[$id]->isRequired())
                        ->setDescription($ctFields[$id]->getDescription());
                    unset($ctFields[$id]);
                }

                // Add any found field directives to the field, so modifiers can use them later.
                $field->setDirectives(Util::getDirectives($selection));

                // Remove directives from node, so fragment printer will not include it.
                $selection->directives = [];
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
    public function getTitlePattern(): string
    {
        return $this->titlePattern;
    }

    /**
     * @param string $titlePattern
     * @return self
     */
    public function setTitlePattern(string $titlePattern): self
    {
        $this->titlePattern = $titlePattern;
        return $this;
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
}
