<?php

namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\CoreBundle\ContentType\ContentType;

class ReferenceAdminViewFieldConfigurator extends GenericFieldConfigurator
{
    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminViewField/referenceAdminView.graphql');
    }

    /**
     * {@inheritDoc}
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType) {
        foreach($field->getDirectives() as $directive) {
            if($directive['name'] === 'referenceField' || $directive['name'] === 'referenceOfField') {

                if(!empty($directive['args']['listView'])) {
                    $field->getConfig()->set('listView', $directive['args']['listView']);
                }

                if(!empty($directive['args']['formView'])) {
                    $field->getConfig()->set('formView', $directive['args']['formView']);
                }

                break;
            }
        }
    }
}
