<?php

namespace UniteCMS\AdminBundle\AdminView;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Printer;
use UniteCMS\CoreBundle\ContentType\ContentType;

class AdminView
{
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
     * @var ?array $filter
     */
    protected $filter = [];

    /**
     * @var array $orderBy
     */
    protected $orderBy;

    /**
     * @var int $limit
     */
    protected $limit = 20;

    /**
     * AdminView constructor.
     *
     * @param FragmentDefinitionNode $fragment
     * @param array $directive
     * @param ContentType $contentType
     * @param string $category
     */
    public function __construct(FragmentDefinitionNode $fragment, array $directive, ContentType $contentType, string $category)
    {
        $this->id = $fragment->name->value;
        $this->name = $directive['args']['name'] ?? $contentType->getName();
        $this->filter = $directive['args']['filter'] ?? [];
        $this->orderBy = $directive['args']['orderBy'] ?? [];
        $this->limit = $directive['args']['limit'] ?? 20;

        $this->category = $category;

        $this->type = $fragment->typeCondition->name->value;

        $fragment->directives = [];
        $this->fragment = Printer::doPrint($fragment);
        $this->fields = [];

        foreach($fragment->selectionSet->selections as $selection) {
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
     * @return array
     */
    public function getFilter(): ?array
    {
        if(empty($this->filter)) {
            return null;
        }

        if(empty($this->filter['field']) && empty($this->filter['AND']) && empty($this->filter['OR'])) {
            return null;
        }

        return $this->filter;
    }

    /**
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }
}
