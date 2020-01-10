<?php


namespace UniteCMS\CoreBundle\DependencyInjection;

use UniteCMS\CoreBundle\Content\ContentValidatorManager;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContentValidatorCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(ContentValidatorManager::class)) {
            return;
        }

        $definition = $container->findDefinition(ContentValidatorManager::class);

        // Register schema extender
        $taggedServices = $container->findTaggedServiceIds('unite.content_validator');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerContentValidator', [new Reference($id)]);
        }
    }
}
