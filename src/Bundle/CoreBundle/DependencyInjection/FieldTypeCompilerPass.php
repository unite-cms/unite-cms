<?php


namespace UniteCMS\CoreBundle\DependencyInjection;

use UniteCMS\CoreBundle\Field\FieldTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(FieldTypeManager::class)) {
            return;
        }

        $definition = $container->findDefinition(FieldTypeManager::class);

        // Register schema extender
        $taggedServices = $container->findTaggedServiceIds('unite.field_type');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerFieldType', [new Reference($id)]);
        }
    }
}
