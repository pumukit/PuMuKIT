<?php

namespace Pumukit\VideoEditorBundle;

use Pumukit\VideoEditorBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PumukitVideoEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
