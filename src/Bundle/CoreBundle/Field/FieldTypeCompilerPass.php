<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.06.17
 * Time: 11:28
 */

namespace UnitedCMS\CoreBundle\Field;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('united.cms.field_type_manager')) {
            return;
        }

        $definition = $container->findDefinition('united.cms.field_type_manager');
        $taggedServices = $container->findTaggedServiceIds('united_cms.field_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerFieldType', array(new Reference($id)));
        }
    }
}