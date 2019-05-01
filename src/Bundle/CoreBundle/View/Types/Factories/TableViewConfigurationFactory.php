<?php

namespace UniteCMS\CoreBundle\View\Types\Factories;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\Types\TableViewConfiguration;

class TableViewConfigurationFactory implements ViewConfigurationFactoryInterface
{
    protected $maxQueryLimit;

    public function __construct(int $maxQueryLimit)
    {
        $this->maxQueryLimit = $maxQueryLimit;
    }

    /**
     * @param Fieldable $fieldable
     * @param FieldTypeManager $fieldTypeManager
     * @return TableViewConfiguration
     */
    public function create(Fieldable $fieldable, FieldTypeManager $fieldTypeManager): ConfigurationInterface
    {
        return new TableViewConfiguration($fieldable, $fieldTypeManager, $this->maxQueryLimit);
    }
}