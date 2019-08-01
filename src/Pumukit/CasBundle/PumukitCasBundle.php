<?php

namespace Pumukit\CasBundle;

use Pumukit\CasBundle\DependencyInjection\Security\Factory\PumukitFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitCasBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new PumukitFactory());
    }
}
