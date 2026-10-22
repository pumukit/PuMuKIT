<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\DependencyInjection;

use Pumukit\CoreBundle\DependencyInjection\FrontendAssets;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class PumukitWizardExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        FrontendAssets::register($container, dirname(__DIR__), '@pumukit/wizard');
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);
    }
}
