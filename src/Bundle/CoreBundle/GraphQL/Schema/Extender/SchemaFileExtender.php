<?php

namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use GraphQL\Type\Schema;

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
    public function extend(Schema $schema): string
    {
        return empty($this->schemaFile) ? '' : file_get_contents($this->schemaFile);
    }
}
