<?php

namespace Pumukit\CasBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pumukit\CasBundle\DependencyInjection\Security\Factory\PumukitFactory;

class PumukitCasBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new PumukitFactory());
    }
}
