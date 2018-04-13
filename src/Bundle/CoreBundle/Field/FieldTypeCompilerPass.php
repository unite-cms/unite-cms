<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.06.17
 * Time: 11:28
 */

namespace UniteCMS\CoreBundle\Field;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('unite.cms.field_type_manager')) {
            return;
        }

        $definition = $container->findDefinition('unite.cms.field_type_manager');
        $taggedServices = $container->findTaggedServiceIds('unite_cms.field_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerFieldType', array(new Reference($id)));
        }
    }
}
