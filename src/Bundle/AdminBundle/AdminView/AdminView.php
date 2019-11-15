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

        $definition->directives = [];
        $this->fragment = Printer::doPrint($definition);
        $this->fields = [];

        foreach($definition->selectionSet->selections as $selection) {
            if($selection instanceof FieldNode) {
                $this->fields[] = new AdminViewField(
                    $selection,
                    $contentType->getField($selection->name->value)
                );
            }
        }
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
