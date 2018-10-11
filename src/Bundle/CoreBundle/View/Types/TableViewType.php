<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewType;

class TableViewType extends ViewType
{
    const TYPE = "table";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Table/index.html.twig";

    const PROPERTY_IDENTIFIERS = ['id', 'locale', 'created', 'updated', 'deleted'];

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $processor = new Processor();
        $settings = $processor->processConfiguration(new TableViewConfiguration($view, self::PROPERTY_IDENTIFIERS), $view->getSettings()->processableConfig());

        foreach($settings['fields'] as $identifier => $definition) {
            if(!in_array($identifier, static::PROPERTY_IDENTIFIERS)) {

                $field = $view->getContentType()->getFields()->get($identifier);
                $fieldType = $this->fieldTypeManager->getFieldType($definition['type']);

                if ($fieldType->getViewFieldConfig($field)) {
                    $settings['fields'][$identifier]['config'] = $fieldType->getViewFieldConfig($field);
                }

                if ($fieldType->getViewFieldAssets($field)) {
                    $settings['fields'][$identifier]['assets'] = $fieldType->getViewFieldAssets($field);
                }
            }
        }

        return array_merge($settings, [
            'View' => $view->getIdentifier(),
            'contentType' => IdentifierNormalizer::graphQLIdentifier($view->getContentType()),
            'hasTranslations' => count($view->getContentType()->getLocales()) > 1,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context)
    {
        $processor = new Processor();
        try {
            $processor->processConfiguration(new TableViewConfiguration($context->getObject(), self::PROPERTY_IDENTIFIERS), $settings->processableConfig());
        }
        catch (\Symfony\Component\Config\Definition\Exception\Exception $e) {
            $context->buildViolation($e->getMessage())->addViolation();
        }
    }
}
