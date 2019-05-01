<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Exception\DeprecationException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Validator\Constraints\Warning;
use UniteCMS\CoreBundle\View\Types\Factories\ViewConfigurationFactoryInterface;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewType;

class TableViewType extends ViewType
{
    const TYPE = "table";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Table/index.html.twig";

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var ViewConfigurationFactoryInterface $tableViewConfigurationFactory;
     */
    protected $tableViewConfigurationFactory;

    public function __construct(FieldTypeManager $fieldTypeManager, ViewConfigurationFactoryInterface $tableViewConfigurationFactory)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->tableViewConfigurationFactory = $tableViewConfigurationFactory;
    }

    protected function createConfig(ContentType $contentType) : ConfigurationInterface {
        return $this->tableViewConfigurationFactory->create($contentType, $this->fieldTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $processor = new Processor();
        $settings = $processor->processConfiguration($this->createConfig($view->getContentType()), $view->getSettings()->processableConfig());

        /**
         * @deprecated 1.0 Remove this, once we drop legacy support.
         */
        unset($settings['columns']);
        unset($settings['sort_asc']);
        unset($settings['sort_field']);

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
        set_error_handler(function($severity, $message, $filename, $lineno){
            throw new DeprecationException($message);
        }, E_USER_DEPRECATED);

        $processor = new Processor();
        try {
            $processor->processConfiguration($this->createConfig($context->getObject()->getContentType()), $settings->processableConfig());
        }
        catch (\Symfony\Component\Config\Definition\Exception\Exception $e) {
            $context->buildViolation($e->getMessage())->addViolation();
        }
        catch (DeprecationException $e) {
            $context->setConstraint(new Warning());
            $context->buildViolation($e->getMessage())->addViolation();
        }
        restore_error_handler();
    }
}
