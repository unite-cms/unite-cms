<?php

namespace UniteCMS\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use UniteCMS\CoreBundle\DependencyInjection\FieldTypeCompilerPass;
use UniteCMS\CoreBundle\DependencyInjection\SchemaManagerCompilerPass;

class UniteCMSCoreBundle extends Bundle
{
    const UNITE_VERSION = '0.10.0';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SchemaManagerCompilerPass());
        $container->addCompilerPass(new FieldTypeCompilerPass());
    }
}
