<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Entity\ContentType;

class GridViewType extends TableViewType
{
    const TYPE = "grid";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Grid/index.html.twig";

    protected function createConfig(ContentType $contentType) : ConfigurationInterface {
        return new GridViewConfiguration($contentType, $this->fieldTypeManager);
    }
}
