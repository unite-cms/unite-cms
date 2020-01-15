<?php

namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\CoreBundle\ContentType\ContentType;

class ReferenceAdminViewConfigurator extends GenericFieldConfigurator
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
            if($directive['name'] === 'referenceAdminView') {

                if(!empty($directive['args']['listView'])) {
                    $field->getConfig()->set('listView', $directive['args']['listView']);
                }

                if(!empty($directive['args']['formView'])) {
                    $field->getConfig()->set('formView', $directive['args']['formView']);
                }

                if(!empty($directive['args']['fieldsInline'])) {
                    $field->getConfig()->set('fieldsInline', $directive['args']['fieldsInline']);
                }

                if(!empty($directive['args']['contentInline'])) {
                    $field->getConfig()->set('contentInline', $directive['args']['contentInline']);
                }

                break;
            }
        }
    }
}
