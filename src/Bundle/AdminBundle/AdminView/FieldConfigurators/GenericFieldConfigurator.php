<?php


namespace UniteCMS\AdminBundle\AdminView\FieldConfigurators;

use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\AdminView\AdminViewField;
use UniteCMS\AdminBundle\AdminView\AdminFieldConfiguratorInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

class GenericFieldConfigurator implements AdminFieldConfiguratorInterface, SchemaProviderInterface
{
    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(SaveExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/AdminViewField/generic.graphql');
    }

    /**
     * @param array $directive
     * @param AdminViewField $field
     */
    protected function processAdminFieldDirective(array $directive, AdminViewField $field) {

        if(!empty($directive['args']['name'])) {
            $field->setName($directive['args']['name']);
        }

        if(!empty($directive['args']['listIf'])) {
            $field->setShowInList((bool)$this->expressionLanguage->evaluate($directive['args']['listIf']));
        }

        if(!empty($directive['args']['formIf'])) {
            $field->setShowInForm((bool)$this->expressionLanguage->evaluate($directive['args']['formIf']));
        }

        if(!empty($directive['args']['formGroup'])) {
            $field->setFormGroup($directive['args']['formGroup']);
        }

        if(!empty($directive['args']['inlineCreate'])) {
            $field->setInlineCreate($directive['args']['inlineCreate']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType) {
        foreach($field->getDirectives() as $directive) {
            if($directive['name'] === 'adminField') {
                $this->processAdminFieldDirective($directive, $field);
                break;
            }
        }
    }
}
