<?php

namespace UniteCMS\CoreBundle\View\Types\Factories;

use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface ViewConfigurationFactoryInterface
{
    /**
     * @param Fieldable $fieldable
     * @param FieldTypeManager $fieldTypeManager
     * @return ConfigurationInterface
     */
    public function create(Fieldable $fieldable, FieldTypeManager $fieldTypeManager): ConfigurationInterface;
}