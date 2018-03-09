<?php

namespace UnitedCMS\CoreBundle\View;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ViewTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('united.cms.view_type_manager')) {
            return;
        }

        $definition = $container->findDefinition('united.cms.view_type_manager');
        $taggedServices = $container->findTaggedServiceIds('united_cms.view_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerViewType', array(new Reference($id)));
        }
    }
}