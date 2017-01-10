<?php

namespace Pumukit\NewAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Pumukit\NewAdminBundle\DependencyInjection\Compiler\MenuPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PumukitNewAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MenuPass());
    }
}
