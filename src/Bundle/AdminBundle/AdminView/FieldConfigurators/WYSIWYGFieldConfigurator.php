<?php


namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\AdminBundle\Exception\InvalidAdminViewFieldConfig;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Field\Types\TextType;

class WYSIWYGFieldConfigurator extends GenericFieldConfigurator
{
    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminViewField/wysiwyg.graphql');
    }

    /**
     * @param ContentTypeField|null $field
     * @param string $type
     */
    protected function checkEscapeSetting(?ContentTypeField $field, string $type) {

        if($field && $field->getSettings()->get('escape') !== false) {
            throw new InvalidAdminViewFieldConfig(sprintf(
                'You can only use the @wysiwygAdminField directive on %s fields where the "escape" setting is set to false, however it is not for field "%s" on type "%s".',
                TextType::getType(),
                $field->getId(),
                $type
            ));
        }
    }

    /**
     * @param array $directive
     * @param AdminViewField $field
     */
    protected function processWysiwygAdminFieldDirective(array $directive, AdminViewField $field) {

        if($field->getFieldType() !== TextType::getType()) {
            throw new InvalidAdminViewFieldConfig(sprintf(
                'You can only use the @wysiwygAdminField directive on fields of type %s, however "%s" is a "%s".',
                TextType::getType(),
                $field->getId(),
                $field->getFieldType()
            ));
        }

        $field->setFieldType('wysiwyg');

        // TODO: Process config.
    }

    /**
     * {@inheritDoc}
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType) {
        foreach($field->getDirectives() as $directive) {
            if($directive['name'] === 'wysiwygAdminField') {

                $this->checkEscapeSetting($contentType->getField($field->getType()), $contentType->getId());

                $this->processAdminFieldDirective($directive, $field);
                $this->processWysiwygAdminFieldDirective($directive, $field);

                break;
            }
        }
    }
}
