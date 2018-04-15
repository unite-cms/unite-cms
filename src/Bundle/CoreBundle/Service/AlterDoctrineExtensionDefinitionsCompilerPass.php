<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 10.12.17
 * Time: 17:42
 */

namespace UniteCMS\CoreBundle\Service;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AlterDoctrineExtensionDefinitionsCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('stof_doctrine_extensions.listener.loggable');
        $tags = $definition->getTags();
        $tags['doctrine.event_subscriber'][0]['priority'] = 10;
        $definition->setTags($tags);
    }
}
