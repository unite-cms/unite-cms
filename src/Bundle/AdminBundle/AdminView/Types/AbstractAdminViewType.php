<?php

namespace UniteCMS\AdminBundle\AdminView\Types;

use GraphQL\Language\AST\FragmentDefinitionNode;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewTypeInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

abstract class AbstractAdminViewType implements AdminViewTypeInterface, SchemaProviderInterface
{
    const TYPE = null;
    const RETURN_TYPE = null;

    /**
     * {@inheritDoc}
     */
    static function getType() : string {
        return static::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminView/' . static::getType() . '.graphql');
    }

    /**
     * {@inheritDoc}
     */
    public function createView(string $category, ContentType $contentType, ?FragmentDefinitionNode $definition = null, ?array $directive = null) : AdminView {
        return new AdminView(static::RETURN_TYPE, $category, $contentType, $definition, $directive);
    }
}
