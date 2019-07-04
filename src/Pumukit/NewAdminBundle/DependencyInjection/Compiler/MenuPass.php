<?php

namespace Pumukit\NewAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class MenuPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // The services tagged as pumukitnewadmin.menuitem (notice the extra 'item' in the name) will be added here
        $this->addItems($container, 'pumukitnewadmin.menu');
        // The services tagged as pumukitnewadmin.mmobjlistbuttonsitem will be added here
        $this->addItems($container, 'pumukitnewadmin.mmobjlistbuttons');
        // The services tagged as pumukitnewadmin.mmobjmenuitem will be added here
        $this->addItems($container, 'pumukitnewadmin.mmobjmenu');
        // The services tagged as pumukitnewadmin.seriesmenu will be added here
        $this->addItems($container, 'pumukitnewadmin.seriesmenu');
    }

    public function addItems($container, $serviceName)
    {
        // always first check if the primary service is defined
        if (!$container->has($serviceName)) {
            return;
        }

        $definition = $container->findDefinition($serviceName);

        // find all service IDs with the item tag
        $taggedServices = $container->findTaggedServiceIds($serviceName.'item');
        foreach ($taggedServices as $id => $tags) {
            // add the transport service to the ItemsList service
            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
