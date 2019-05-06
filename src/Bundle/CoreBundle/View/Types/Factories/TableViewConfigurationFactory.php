<?php

namespace UniteCMS\CoreBundle\View\Types\Factories;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\Types\TableViewConfiguration;

class TableViewConfigurationFactory implements ViewConfigurationFactoryInterface
{
    protected $maxQueryLimit;
    protected $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager, int $maxQueryLimit)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->maxQueryLimit = $maxQueryLimit;
    }

    /**
     * @param Fieldable $fieldable
     * @return TableViewConfiguration
     */
    public function create(Fieldable $fieldable): ConfigurationInterface
    {
        return new TableViewConfiguration($fieldable, $this->fieldTypeManager, $this->maxQueryLimit);
    }
}