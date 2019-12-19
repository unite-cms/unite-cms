<?php

namespace UniteCMS\AdminBundle\AdminView;

use GraphQL\Language\AST\FragmentDefinitionNode;
use UniteCMS\CoreBundle\ContentType\ContentType;

interface AdminViewTypeInterface
{
    static function getType(): string;
    public function createView(string $category, ?ContentType $contentType = null, ?FragmentDefinitionNode $definition = null, ?array $directive = null) : AdminView;
}
