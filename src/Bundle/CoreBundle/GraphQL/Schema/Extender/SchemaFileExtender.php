<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\GraphQL\ExecutionContext;

class SchemaFileExtender implements SchemaExtenderInterface
{
    /**
     * @var string $schemaFile
     */
    protected $schemaFile;

    public function __construct(string $schemaFile = '')
    {
        $this->schemaFile = $schemaFile;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema, ExecutionContext $context): string
    {
        return empty($this->schemaFile) ? '' : file_get_contents($this->schemaFile);
    }
}
