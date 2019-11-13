<?php

namespace UniteCMS\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use UniteCMS\CoreBundle\DependencyInjection\FieldTypeCompilerPass;
use UniteCMS\CoreBundle\DependencyInjection\SchemaManagerCompilerPass;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Scalar\ScalarResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Type\TypeResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Extender\SchemaExtenderInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Modifier\SchemaModifierInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;

class UniteCMSCoreBundle extends Bundle
{
    const UNITE_VERSION = '0.10.0';

    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(FieldTypeInterface::class)->addTag('unite.field_type');
        $container->registerForAutoconfiguration(SchemaProviderInterface::class)->addTag('unite.graphql.schema_provider');
        $container->registerForAutoconfiguration(SchemaExtenderInterface::class)->addTag('unite.graphql.schema_extender', ['position' => SchemaExtenderInterface::EXTENDER_AFTER]);
        $container->registerForAutoconfiguration(SchemaModifierInterface::class)->addTag('unite.graphql.schema_modifier');
        $container->registerForAutoconfiguration(FieldResolverInterface::class)->addTag('unite.graphql.field_resolver');
        $container->registerForAutoconfiguration(TypeResolverInterface::class)->addTag('unite.graphql.type_resolver');
        $container->registerForAutoconfiguration(ScalarResolverInterface::class)->addTag('unite.graphql.scalar_resolver');

        $container->addCompilerPass(new SchemaManagerCompilerPass());
        $container->addCompilerPass(new FieldTypeCompilerPass());
    }
}
