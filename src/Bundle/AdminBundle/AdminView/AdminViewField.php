<?php

namespace UniteCMS\AdminBundle\AdminView;

use GraphQL\Language\AST\FieldNode;
use UniteCMS\AdminBundle\Exception\InvalidAdminViewSelection;
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
     * AdminView constructor.
     *
     * @param FieldNode $fieldNode
     * @param ContentTypeField|null $contentTypeField
     */
    public function __construct(FieldNode $fieldNode, ?ContentTypeField $contentTypeField = null)
    {
        $this->id = $fieldNode->name->value;
        $this->name = $fieldNode->alias ? $fieldNode->alias->value : $this->id;

        if($contentTypeField) {
            $this->name = $contentTypeField->getName();
            $this->type = $contentTypeField->getType();
        }

        else if ($this->id === 'id') {
            $this->type = 'id';
        }

        else {
            throw new InvalidAdminViewSelection();
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
    public function getName(): string
    {
        return $this->name;
    }

    public function getType() : string {
        return $this->type;
    }
}
