<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Provider;

class SchemaFileProvider implements SchemaProviderInterface
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
    public function extend(): string
    {
        return empty($this->schemaFile) ? '' : file_get_contents($this->schemaFile);
    }
}
