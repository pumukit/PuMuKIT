<?php

namespace Pumukit\NewAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class MenuPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('pumukitnewadmin.menu')) {
            return;
        }

        $definition = $container->findDefinition('pumukitnewadmin.menu');

        // find all service IDs with the pumukitnewadmin.menuitem tag
        $taggedServices = $container->findTaggedServiceIds('pumukitnewadmin.menuitem');

        foreach ($taggedServices as $id => $tags) {
            // add the transport service to the Chain service
            $definition->addMethodCall('add', array(new Reference($id)));
        }
    }
}
