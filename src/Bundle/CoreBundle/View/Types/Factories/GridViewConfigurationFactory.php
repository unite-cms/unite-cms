<?php

namespace UniteCMS\CoreBundle\View\Types\Factories;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\Types\GridViewConfiguration;

class GridViewConfigurationFactory implements ViewConfigurationFactoryInterface
{
    protected $maxQueryLimit;

    public function __construct(int $maxQueryLimit)
    {
        $this->maxQueryLimit = $maxQueryLimit;
    }

    /**
     * @param Fieldable $fieldable
     * @param FieldTypeManager $fieldTypeManager
     * @return GridViewConfiguration
     */
    public function create(Fieldable $fieldable, FieldTypeManager $fieldTypeManager): ConfigurationInterface
    {
        return new GridViewConfiguration($fieldable, $fieldTypeManager, $this->maxQueryLimit);
    }
}