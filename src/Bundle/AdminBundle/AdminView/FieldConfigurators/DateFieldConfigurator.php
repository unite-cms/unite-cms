<?php


namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\CoreBundle\ContentType\ContentType;

class DateFieldConfigurator extends GenericFieldConfigurator
{
    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminViewField/date.graphql');
    }

    /**
     * {@inheritDoc}
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType) {
        foreach($field->getDirectives() as $directive) {
            if($directive['name'] === 'dateAdminField') {
                $field->getConfig()->set('format', $directive['args']['format'] ?? null);
                break;
            }
        }
    }
}
