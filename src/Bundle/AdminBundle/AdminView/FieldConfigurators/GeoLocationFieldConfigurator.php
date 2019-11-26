<?php


namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\AdminBundle\Exception\InvalidAdminViewFieldConfig;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Field\Types\GeoLocationType;

class GeoLocationFieldConfigurator extends GenericFieldConfigurator
{
    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminViewField/geoLocation.graphql');
    }

    /**
     * @param array $directive
     * @param AdminViewField $field
     */
    protected function processGeoLocationAdminFieldDirective(array $directive, AdminViewField $field) {

        if($field->getFieldType() !== GeoLocationType::getType()) {
            throw new InvalidAdminViewFieldConfig(sprintf(
                'You can only use the @geoLocationAdminField directive on fields of type %s, however "%s" is a "%s".',
                GeoLocationType::getType(),
                $field->getId(),
                $field->getFieldType()
            ));
        }

        if(!empty($directive['args']['algolia'])) {
            $field->getConfig()->set('algolia', $directive['args']['algolia']);
        }

        else if(!empty($directive['args']['google'])) {
            $field->getConfig()->set('google', $directive['args']['google']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType) {
        foreach($field->getDirectives() as $directive) {
            if($directive['name'] === 'geoLocationAdminField') {
                $this->processAdminFieldDirective($directive, $field);
                $this->processGeoLocationAdminFieldDirective($directive, $field);
                break;
            }
        }
    }
}
