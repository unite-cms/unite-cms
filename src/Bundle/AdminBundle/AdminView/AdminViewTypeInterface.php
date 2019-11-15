<?php

namespace UniteCMS\AdminBundle\AdminView;

use GraphQL\Language\AST\FragmentDefinitionNode;
use UniteCMS\CoreBundle\ContentType\ContentType;

interface AdminViewTypeInterface
{
    static function getType(): string;
    public function createView(FragmentDefinitionNode $definition, array $directive, string $category, ContentType $contentType);
}
