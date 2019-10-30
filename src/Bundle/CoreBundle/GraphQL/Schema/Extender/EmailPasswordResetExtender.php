<?php


namespace UniteCMS\CoreBundle\GraphQL\Schema\Extender;

use GraphQL\Type\Schema;

class EmailPasswordResetExtender implements SchemaExtenderInterface
{
    /**
     * {@inheritDoc}
     */
    public function extend(Schema $schema): string
    {
        return file_get_contents(__DIR__ . '/../../../Resources/GraphQL/Schema/email-password-reset.graphql');
    }
}
