<?php

namespace UniteCMS\CoreBundle\View\Types\Factories;

use UniteCMS\CoreBundle\Entity\Fieldable;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface ViewConfigurationFactoryInterface
{
    /**
     * @param Fieldable $fieldable
     * @return ConfigurationInterface
     */
    public function create(Fieldable $fieldable): ConfigurationInterface;
}