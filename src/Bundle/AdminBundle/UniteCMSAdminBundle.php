<?php

namespace UniteCMS\AdminBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use UniteCMS\AdminBundle\AdminView\AdminFieldConfiguratorInterface;
use UniteCMS\AdminBundle\AdminView\AdminViewTypeInterface;
use UniteCMS\AdminBundle\DependencyInjection\AdminViewTypeCompilerPass;

use UniteCMS\CoreBundle\UniteCMSCoreBundle;

class UniteCMSAdminBundle extends Bundle
{
    const UNITE_VERSION = UniteCMSCoreBundle::UNITE_VERSION;

    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AdminViewTypeInterface::class)->addTag('unite.admin_view_type');
        $container->registerForAutoconfiguration(AdminFieldConfiguratorInterface::class)->addTag('unite.admin_view_field_configurator');
        $container->addCompilerPass(new AdminViewTypeCompilerPass());
    }
}
