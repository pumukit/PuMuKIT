<?php
namespace Pumukit\VideoEditorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('pumukitschema.mmsduration');
        $definition->setClass('Pumukit\VideoEditorBundle\Services\MultimediaObjectDurationService');
    }
}
