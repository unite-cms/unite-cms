<?php

namespace UniteCMS\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use UniteCMS\CoreBundle\View\ViewTypeCompilerPass;
use UniteCMS\CoreBundle\Field\FieldTypeCompilerPass;
use UniteCMS\CoreBundle\Service\AlterDoctrineExtensionDefinitionsCompilerPass;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeCompilerPass;

class UniteCMSCoreBundle extends Bundle
{
    const UNITE_VERSION = '0.8.2';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FieldTypeCompilerPass());
        $container->addCompilerPass(new ViewTypeCompilerPass());
        $container->addCompilerPass(new AlterDoctrineExtensionDefinitionsCompilerPass());
        $container->addCompilerPass(new SchemaTypeCompilerPass());
    }
}
